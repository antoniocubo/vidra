import { expect, test, type Page } from '@playwright/test';

const SENHA = 'laboratorio-e2e-2026';
const MAILPIT = 'http://127.0.0.1:8025/api/v1';
const sufixo = Date.now().toString(36);

/** CNPJ válido: 8 dígitos de base, filial 0001 e os dois dígitos verificadores. */
function gerarCnpj(): string {
    const digitos = Array.from({ length: 8 }, () => Math.floor(Math.random() * 10)).concat([0, 0, 0, 1]);
    for (const tamanho of [12, 13]) {
        const pesos = tamanho === 12 ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2] : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        const soma = digitos.slice(0, tamanho).reduce((total, d, i) => total + d * pesos[i], 0);
        digitos.push(soma % 11 < 2 ? 0 : 11 - (soma % 11));
    }
    return digitos.join('');
}

async function escolher(page: Page, campo: string, opcao: string | RegExp) {
    await page.getByRole('combobox', { name: campo }).click();
    await page.getByRole('option', { name: opcao }).first().click();
}

type Mensagem = { ID: string; Subject: string; Attachments: number };

/** Espera o e-mail chegar no Mailpit e devolve o corpo (texto e HTML) e os anexos. */
async function emailPara(destino: string, assunto: RegExp) {
    let achada: { Text: string; HTML: string; Subject: string; Attachments: { FileName: string }[] } | undefined;
    await expect(async () => {
        const busca = await fetch(`${MAILPIT}/search?query=${encodeURIComponent(`to:${destino}`)}`);
        const { messages } = (await busca.json()) as { messages: Mensagem[] };
        const mensagem = messages.find((m) => assunto.test(m.Subject));
        expect(mensagem, `e-mail para ${destino} com assunto ${assunto}`).toBeDefined();
        achada = await (await fetch(`${MAILPIT}/message/${mensagem!.ID}`)).json();
    }).toPass({ timeout: 60_000 });
    return achada!;
}

async function linkDoEmail(destino: string, padrao: RegExp, assunto = /.*/) {
    const email = await emailPara(destino, assunto);
    const link = `${email.Text}\n${email.HTML}`.match(padrao)?.[0];
    expect(link, 'link no corpo do e-mail').toBeDefined();
    return link!.replace(/&amp;/g, '&').replace(/["'<>)]+$/, '');
}

test('do cadastro do laboratório ao laudo entregue ao tutor', async ({ page, browser }) => {
    const admin = { nome: 'Marina Alves', email: `marina.${sufixo}@e2e.vidra.test` };
    const bancada = { nome: 'Carlos Bancada', email: `carlos.${sufixo}@e2e.vidra.test` };
    const vet = { email: `helena.${sufixo}@e2e.vidra.test` };
    const tutor = { email: `paulo.${sufixo}@e2e.vidra.test` };
    let paginaBancada: Page;
    let codigoDaEtiqueta = '';

    await test.step('cadastra o laboratório (o administrador é o RT)', async () => {
        await page.goto('/cadastro');
        await page.getByLabel('Nome do laboratório').fill(`Laboratório E2E ${sufixo}`);
        await page.getByLabel('CNPJ').fill(gerarCnpj());
        await page.getByLabel('Telefone').fill('1932427180');
        await page.getByLabel('CEP').fill('13010-000');
        await page.getByLabel('Cidade').fill('Campinas');
        await escolher(page, 'UF', /São Paulo/);
        await page.getByLabel('Endereço').fill('Rua das Amostras, 100');
        await page.getByRole('button', { name: 'Continuar para o responsável técnico' }).click();

        await page.getByLabel('Nome completo').fill(admin.nome);
        await page.getByLabel('CRMV').fill('12345');
        await escolher(page, 'UF', /São Paulo/);
        await page.getByRole('button', { name: 'Continuar para o seu acesso' }).click();

        await page.getByLabel('E-mail (é o seu login)').fill(admin.email);
        await page.getByLabel('Celular', { exact: true }).fill('19999990000');
        await page.getByLabel('Crie uma senha').fill(SENHA);
        await page.getByLabel(/Li e aceito os termos/).check();
        await page.getByRole('button', { name: 'Escolher o plano' }).click();
        await page.getByRole('button', { name: 'Começar o teste de 14 dias' }).click();
        await expect(page).toHaveURL(/dashboard/);
    });

    await test.step('convida uma pessoa da bancada e ela aceita pelo link do e-mail', async () => {
        await page.goto('/equipe');
        await page.getByRole('button', { name: 'Convidar pessoa' }).first().click();
        const dialogo = page.getByRole('dialog');
        await dialogo.getByLabel('Nome').fill(bancada.nome);
        await dialogo.getByRole('textbox', { name: 'E-mail' }).fill(bancada.email);
        await dialogo.getByText('Bancada', { exact: true }).click();
        await dialogo.getByRole('button', { name: 'Convidar pessoa' }).click();
        await expect(page.getByText(bancada.email, { exact: true })).toBeVisible();

        const link = await linkDoEmail(bancada.email, /https?:\/\/\S*\/convite\/[\w-]+/);
        const contexto = await browser.newContext({ locale: 'pt-BR' });
        paginaBancada = await contexto.newPage();
        await paginaBancada.goto(link);
        await paginaBancada.getByLabel('Seu nome').fill(bancada.nome);
        await paginaBancada.getByLabel('Crie sua senha').fill(SENHA);
        await paginaBancada.getByLabel('Confirme sua senha').fill(SENHA);
        await paginaBancada.getByRole('button', { name: 'Criar conta e aceitar convite' }).click();
        await expect(paginaBancada).toHaveURL(/dashboard/);

        const menu = paginaBancada;
        for (const item of ['Painel', 'Amostras', 'Bancada', 'Laudos'])
            await expect(menu.getByRole('link', { name: item, exact: true }).first()).toBeVisible();
        for (const item of ['Pedidos', 'Liberação', 'Equipe', 'Financeiro', 'Exames'])
            await expect(menu.getByRole('link', { name: item, exact: true })).toHaveCount(0);
    });

    await test.step('cadastra o exame com valor de referência e valor crítico', async () => {
        await page.goto('/exames/novo');
        await page.getByLabel('Código').fill('GLI');
        await page.getByLabel('Nome do exame').fill('Glicose');
        await page.getByLabel('Material').fill('Soro');
        await escolher(page, 'Setor', 'Bioquímica');
        await page.getByLabel('Preço (R$)').fill('25,00');
        await page.getByLabel('Nome do analito').fill('Glicose');
        await page.getByLabel('Unidade').fill('mg/dL');
        await page.getByLabel('Casas decimais').fill('0');
        await page.getByRole('button', { name: 'Salvar exame' }).click();
        await expect(page).toHaveURL(/\/exames/);

        await page.getByRole('link', { name: 'Editar referências' }).click();
        await page.getByLabel(/^Mínimo · Cão adulto/).fill('70');
        await page.getByLabel(/^Máximo · Cão adulto/).fill('110');
        await page.getByLabel(/^Crítico baixo · Cão adulto/).fill('40');
        await page.getByLabel(/^Crítico alto · Cão adulto/).fill('400');
        await page.getByRole('button', { name: 'Salvar referências de Glicose' }).click();
        await expect(page.getByText('Referências salvas')).toBeVisible();
    });

    await test.step('cadastra o veterinário, o tutor com autorização de e-mail e o animal', async () => {
        await page.goto('/veterinarios/novo');
        await page.getByLabel('Nome completo').fill('Helena Prado');
        await page.getByLabel('CRMV', { exact: true }).fill(String(10000 + (Date.now() % 80000)));
        await escolher(page, 'UF', 'SP');
        await page.getByRole('textbox', { name: 'E-mail' }).fill(vet.email);
        await page.getByLabel('Celular', { exact: true }).fill('19988887777');
        await page.getByLabel('Telefone para valor crítico').fill('1932001122');
        await page.getByText('O tutor, no balcão').click();
        await page.getByRole('button', { name: 'Salvar veterinário' }).click();
        await expect(page).toHaveURL(/\/veterinarios\?veterinario=/);

        await page.goto('/tutores/novo');
        await page.getByLabel('Nome completo').fill('Paulo Tutor');
        await page.getByLabel('Celular com DDD').fill('19977776666');
        await page.getByRole('textbox', { name: 'E-mail' }).fill(tutor.email);
        await page.getByLabel(/autoriza receber os laudos por e-mail/).check();
        await page.getByLabel('Nome do animal').fill('Thor');
        await expect(page.getByRole('button', { name: /Cão/, pressed: true })).toBeVisible();
        await escolher(page, 'Sexo', 'Macho');
        await page.getByRole('button', { name: 'Idade aproximada' }).click();
        await page.getByLabel('Anos').fill('4');
        await page.getByLabel('Meses').fill('0');
        await escolher(page, 'Castrado', 'Sim');
        await page.getByRole('button', { name: 'Salvar tutor e animais' }).click();
        await expect(page.getByText('Thor').first()).toBeVisible();
    });

    await test.step('abre o pedido', async () => {
        await page.goto('/pedidos/novo');
        await escolher(page, 'Veterinário solicitante', /Helena/);
        await page.getByText('O tutor no balcão').click();
        await page
            .getByLabel(/Buscar/)
            .first()
            .fill('Thor');
        await page.getByRole('button', { name: /Thor/ }).click();
        await page.getByLabel('Incluir exame').fill('Glicose');
        await page.getByRole('button', { name: /GLI/ }).click();
        await page.getByRole('button', { name: /Salvar e imprimir 1 etiqueta/ }).click();
        await expect(page).toHaveURL(/\/pedidos\/[0-9a-f-]{36}/);
        codigoDaEtiqueta = (
            await page
                .getByRole('cell', { name: /^A-\d+-\d+$/ })
                .first()
                .innerText()
        ).trim();
    });

    await test.step('recebe a amostra bipando o código da etiqueta', async () => {
        await page.goto('/amostras');
        const leitor = page.getByLabel('Bipe a etiqueta ou digite o código');
        await leitor.fill(codigoDaEtiqueta);
        await leitor.press('Enter');
        await expect(page.getByText(/recebida/i).first()).toBeVisible();
    });

    await test.step('a bancada digita um resultado crítico e envia para conferência', async () => {
        await paginaBancada.goto('/bancada');
        await paginaBancada.getByRole('link', { name: /Thor/ }).click();
        await expect(paginaBancada).toHaveURL(/item=/); // a visita do Inertia termina aqui; digitar antes disso perde o valor
        await expect(paginaBancada.getByRole('heading', { name: 'Glicose · Thor' })).toBeVisible();
        await paginaBancada.getByLabel('Resultado de Glicose').fill('450');
        await expect(paginaBancada.getByText(/Crítico alto/)).toBeVisible();
        await paginaBancada.getByRole('button', { name: 'Salvar resultados' }).click();
        await expect(paginaBancada.getByRole('button', { name: 'Enviar para conferência' })).toBeEnabled();
        await paginaBancada.getByRole('button', { name: 'Enviar para conferência' }).click();
        await expect(paginaBancada.getByRole('tab', { name: 'Bioquímica 0' })).toBeVisible(); // o item saiu da bancada
    });

    await test.step('o RT precisa registrar o aviso do valor crítico antes de liberar', async () => {
        await page.goto('/liberacao');
        await page.getByRole('link', { name: /Thor/ }).first().click();
        await expect(page.getByRole('button', { name: 'Liberar laudo' })).toBeDisabled();
        await expect(page.getByText(/Registre o aviso do valor crítico de Glicose/)).toBeVisible();

        await page.getByRole('button', { name: 'Registrar que avisei a clínica' }).click();
        const avisei = page.getByLabel('Avisei');
        await expect(avisei).toBeVisible();
        await avisei.press('Enter'); // envia o formulário do aviso sem depender do botão estar parado na tela
        await expect(page.getByText(/Aviso registrado para/)).toBeVisible();

        await page.getByRole('button', { name: 'Liberar laudo' }).click();
        await page.getByRole('dialog').getByRole('button', { name: 'Liberar laudo' }).click();
        await expect(page.getByText(/Liberado/).first()).toBeVisible();
    });

    await test.step('o tutor recebe o e-mail com o PDF e abre o laudo pelo link', async () => {
        const email = await emailPara(tutor.email, /^Laudo do Thor/);
        expect(email.Attachments.map((a) => a.FileName).join()).toMatch(/\.pdf$/i);
        const link = `${email.Text}\n${email.HTML}`.match(/https?:\/\/[^\s"'<>]*\/laudo\/[^\s"'<>]+/)?.[0];
        expect(link, 'link do laudo no e-mail').toBeDefined();

        const contexto = await browser.newContext({ locale: 'pt-BR' });
        const paginaTutor = await contexto.newPage();
        await paginaTutor.goto(link!.replace(/&amp;/g, '&'));
        await expect(paginaTutor.getByText('Thor').first()).toBeVisible();
        await expect(paginaTutor.getByText('450 mg/dL')).toBeVisible();
        await expect(paginaTutor.getByText(/Crítico/).first()).toBeVisible();
    });

    // O pedido é do mês corrente e "Fechar agora" fecha o mês anterior: aqui só vale a tela abrir
    // e o botão rodar sem erro. A fatura de verdade (clínica, desconto, e-mail) fica nos testes Feature do Financeiro.
    await test.step('abre o financeiro e fecha o mês anterior sem erro', async () => {
        await page.goto('/financeiro');
        await expect(page.getByRole('heading', { name: /^Faturas de / })).toBeVisible();
        await expect(page.getByLabel('Competência')).toBeVisible();
        await page.getByRole('button', { name: 'Fechar agora' }).click();
        await page.getByRole('dialog').getByRole('button', { name: 'Fechar agora' }).click();
        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(page.getByRole('heading', { name: /^Faturas de / })).toBeVisible();
        await expect(page.getByText('Internal Server Error')).toHaveCount(0);
    });
});
