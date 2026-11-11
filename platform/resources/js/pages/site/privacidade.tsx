// Texto provisório: precisa de revisão jurídica antes de publicar.
import SiteLegalLayout, { Secao } from '@/layouts/site-legal-layout';

export default function Privacidade() {
    return (
        <SiteLegalLayout titulo="Política de privacidade" vigencia="10/11/2026">
            <p className="text-[15px] leading-6">
                Esta política explica como o Vidra trata dados pessoais, de acordo com a Lei Geral de Proteção de Dados (Lei 13.709/2018, a LGPD).
            </p>

            <Secao titulo="1. Quem é quem">
                <p>
                    O Vidra é um sistema de laboratório em nuvem, oferecido por [RAZÃO SOCIAL E CNPJ DA VIDRA]. Cada laboratório que usa o Vidra é o{' '}
                    <strong>controlador</strong> dos dados dos seus tutores, animais, veterinários solicitantes e clínicas: ele decide para que esses
                    dados são usados. O Vidra atua como <strong>operador</strong>: trata esses dados em nome do laboratório, só para prestar o serviço
                    contratado.
                </p>
                <p>
                    Sobre os dados dos usuários do próprio laboratório (login, e-mail, papel) e sobre a assinatura do Vidra, o Vidra é o controlador.
                </p>
            </Secao>

            <Secao titulo="2. Quais dados tratamos">
                <ul>
                    <li>Tutor: nome, telefone, e-mail e, se o laboratório informar, CPF.</li>
                    <li>Animal: nome, espécie, raça, sexo, idade e os resultados dos exames.</li>
                    <li>Veterinário solicitante: nome, CRMV, e-mail e telefone de contato.</li>
                    <li>Clínica parceira: razão social, CNPJ e contato para a fatura.</li>
                    <li>Usuários do laboratório: nome, e-mail, papel de acesso e registros de uso (quem fez o quê e quando).</li>
                </ul>
            </Secao>

            <Secao titulo="3. Para que usamos e em que base legal">
                <ul>
                    <li>Emitir, assinar e entregar laudos: execução do contrato entre o laboratório e a clínica ou o tutor.</li>
                    <li>Faturar as clínicas e o laboratório: execução de contrato e cumprimento de obrigação legal.</li>
                    <li>Manter a segurança e a trilha de auditoria do sistema: legítimo interesse.</li>
                    <li>Enviar o laudo ao tutor por e-mail: consentimento, descrito abaixo.</li>
                </ul>
            </Secao>

            <Secao titulo="4. Autorização do tutor para receber o laudo por e-mail">
                <p>
                    O tutor autoriza, ou não, o recebimento do laudo por e-mail. A autorização fica registrada com data, hora e o atendente que a
                    coletou. Sem ela, o laudo não é enviado por e-mail. O tutor pode retirar a autorização a qualquer momento, pedindo ao laboratório.
                </p>
            </Secao>

            <Secao titulo="5. Com quem compartilhamos">
                <p>Usamos provedores para prestar o serviço, sob contrato:</p>
                <ul>
                    <li>Hospedagem em servidor virtual (VPS) na Hetzner.</li>
                    <li>E-mail transacional, para enviar laudos, convites e avisos.</li>
                    <li>Asaas, para cobrar a assinatura do laboratório.</li>
                </ul>
                <p>Os laudos são vistos apenas pelo laboratório, pelo veterinário solicitante e pelo tutor, pelo link com código de acesso.</p>
            </Secao>

            <Secao titulo="6. Por quanto tempo guardamos">
                <p>
                    Os laudos e cadastros ficam guardados pelo prazo que o laboratório definir e pelo que a lei exigir. Quando uma conta é encerrada,
                    os dados são excluídos depois de [PRAZO DE EXCLUSÃO], salvo o que precisarmos guardar por obrigação legal.
                </p>
            </Secao>

            <Secao titulo="7. Seus direitos">
                <p>
                    Você pode pedir confirmação de que tratamos seus dados, acesso, correção, anonimização, portabilidade, eliminação e informação
                    sobre com quem compartilhamos. Se você é tutor ou veterinário, o pedido é, em regra, atendido pelo laboratório (o controlador); o
                    Vidra ajuda o laboratório a responder. Para qualquer pedido, escreva para o encarregado: [E-MAIL DO ENCARREGADO].
                </p>
            </Secao>

            <Secao titulo="8. Segurança">
                <ul>
                    <li>Os dados de cada laboratório são isolados dos dos outros, no aplicativo e no banco de dados.</li>
                    <li>A comunicação usa criptografia em trânsito (HTTPS).</li>
                    <li>O acesso é por conta pessoal, e ações importantes ficam registradas.</li>
                </ul>
            </Secao>

            <Secao titulo="9. Contato do encarregado">
                <p>[RAZÃO SOCIAL E CNPJ DA VIDRA] · [E-MAIL DO ENCARREGADO]</p>
            </Secao>

            <Secao titulo="10. Mudanças nesta política">
                <p>Quando mudarmos esta política, avisaremos os laboratórios por e-mail e atualizaremos a data de vigência no topo.</p>
            </Secao>
        </SiteLegalLayout>
    );
}
