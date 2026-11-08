import {
    AiBadge,
    Alert,
    Button,
    Card,
    Checkbox,
    ChipToggleGroup,
    ConfirmDialog,
    CountPill,
    DataTable,
    DetailList,
    EmptyState,
    FormActions,
    FormField,
    FormSection,
    IconButton,
    LabSwitcher,
    MetricCard,
    PageHeader,
    PersonRow,
    ProductBadge,
    ProgressBar,
    RadioCardGroup,
    ResultTable,
    ScanField,
    SegmentedControl,
    Select,
    StatusBadge,
    StatusStepper,
    Switch,
    TabPanel,
    Tabs,
    TextInput,
    Toast,
    type Column,
    type LabOption,
    type ResultRowData,
    type ScanState,
    type Tone,
} from '@/components/vidra';
import { AnimalCard, SampleLabel } from '@/components/vidra/pet';
import { Head } from '@inertiajs/react';
import { Inbox, MoreHorizontal, Plus, Printer, Search, X } from 'lucide-react';
import { useState, type ReactNode } from 'react';

/*
 * Vitrine do design system. Só existe em local e testing (routes/dev.php).
 * Cada seção corresponde a uma folha da página "Componentes" do protótipo.
 */

type Pedido = { protocolo: string; animal: string; exames: string; situacao: string; tone: Tone; prazo: string; critico?: boolean };

const PEDIDOS: Pedido[] = [
    {
        protocolo: 'P-00412',
        animal: 'Thor · cão · Carla M.',
        exames: 'Bioquímico, Hemograma',
        situacao: 'Valor crítico',
        tone: 'crit',
        prazo: '12:00',
        critico: true,
    },
    {
        protocolo: 'P-00409',
        animal: 'Mel · gata · João P.',
        exames: 'Urinálise, Creatinina',
        situacao: 'Aguardando liberação',
        tone: 'warn',
        prazo: '13:30',
    },
    { protocolo: 'P-00415', animal: 'Luna · cadela · Ana R.', exames: 'Hemograma', situacao: 'Em bancada', tone: 'info', prazo: '14:00' },
    { protocolo: 'P-00401', animal: 'Fred · cão · Paula V.', exames: 'Hemograma, Bioquímico', situacao: 'Entregue', tone: 'ok', prazo: '09:58' },
];

const COLUNAS: ReadonlyArray<Column<Pedido>> = [
    { key: 'protocolo', header: 'Protocolo', mono: true, cell: (p) => p.protocolo },
    { key: 'animal', header: 'Animal', cell: (p) => p.animal },
    { key: 'exames', header: 'Exames', cell: (p) => p.exames },
    { key: 'situacao', header: 'Situação', cell: (p) => <StatusBadge tone={p.tone}>{p.situacao}</StatusBadge> },
    { key: 'prazo', header: 'Prazo', align: 'right', mono: true, cell: (p) => p.prazo },
];

const RESULTADOS: ResultRowData[] = [
    { id: 'alt', parametro: 'ALT', valor: '54', unidade: 'U/L', referencia: '10 a 88', flag: 'normal' },
    { id: 'ureia', parametro: 'Ureia', valor: '142', unidade: 'mg/dL', referencia: '21 a 60', flag: 'alto' },
    { id: 'creat', parametro: 'Creatinina', valor: '2,4', unidade: 'mg/dL', referencia: '0,5 a 1,5', flag: 'alto', anterior: 'era 1,1 em 12/08' },
    { id: 'alb', parametro: 'Albumina', valor: '2,1', unidade: 'g/dL', referencia: '2,6 a 3,3', flag: 'baixo' },
    { id: 'k', parametro: 'Potássio', valor: '7,2', unidade: 'mEq/L', referencia: '3,5 a 5,8', flag: 'critico' },
];

const LABS: LabOption[] = [
    { id: 'alfa', name: 'Laboratório Alfa Vet', city: 'Campinas', initials: 'AV' },
    { id: 'beta', name: 'Lab Beta Diagnóstico', city: 'Valinhos', initials: 'LB', color: '#5B3E8C', unread: 2, hasCritical: true },
    { id: 'centro', name: 'Centro Vet Análises', city: 'Sumaré', initials: 'CV', color: '#8A4B12' },
];

function Secao({ id, title, children }: { id: string; title: string; children: ReactNode }) {
    return (
        <section aria-labelledby={id} className="flex flex-col gap-4">
            <h2 id={id} className="font-display text-2xl font-semibold">
                {title}
            </h2>
            {children}
        </section>
    );
}

function Exemplo({ nome, children }: { nome: string; children: ReactNode }) {
    return (
        <div className="flex flex-col gap-2">
            {children}
            <code className="text-muted-foreground font-mono text-[11px]">{nome}</code>
        </div>
    );
}

export default function VitrineDeComponentes() {
    const [locais, setLocais] = useState<string[]>(['vetcare']);
    const [especie, setEspecie] = useState('cao');
    const [prioridade, setPrioridade] = useState<'rotina' | 'urgente'>('rotina');
    const [pagador, setPagador] = useState<'clinica' | 'tutor' | 'indisponivel'>('clinica');
    const [acesso, setAcesso] = useState(true);
    const [aba, setAba] = useState<'exames' | 'amostras' | 'historico'>('exames');
    const [lab, setLab] = useState('alfa');
    const [selecionado, setSelecionado] = useState('P-00415');
    const [dialogo, setDialogo] = useState(false);
    const [scan, setScan] = useState<{ state: ScanState; message?: string }>({ state: 'idle' });

    const lerAmostra = (code: string) =>
        setScan(
            code.startsWith('A-0412')
                ? { state: 'success', message: `${code} recebida · Thor · soro, tampa vermelha` }
                : { state: 'error', message: `Código ${code} não existe neste laboratório.` },
        );

    return (
        <div className="bg-background text-foreground min-h-screen">
            <Head title="Componentes" />
            <main className="mx-auto flex max-w-6xl flex-col gap-12 px-4 py-10 md:px-8">
                <PageHeader
                    eyebrow="Design system · só em desenvolvimento"
                    title="Componentes da Vidra"
                    description="Tudo de resources/js/components/vidra, com dados de exemplo. Teste com o teclado: Tab, setas e Enter."
                />

                <Secao id="acoes" title="1 · Ações">
                    <div className="flex flex-wrap items-end gap-4">
                        <Button>Liberar laudo</Button>
                        <Button variant="secondary">Pedir recoleta</Button>
                        <Button variant="ghost">Ver histórico</Button>
                        <Button variant="danger">Rejeitar amostra</Button>
                        <Button icon={Plus}>Novo pedido</Button>
                        <Button variant="secondary" icon={Printer}>
                            Imprimir etiquetas
                        </Button>
                        <Button loading>Salvando</Button>
                        <Button disabled>Desabilitado</Button>
                        <IconButton icon={MoreHorizontal} label="Mais ações" variant="secondary" />
                        <IconButton icon={X} label="Fechar" variant="ghost" />
                    </div>
                    <div className="flex flex-wrap items-end gap-4">
                        <Button size="lg">Abrir laudo completo</Button>
                        <Button size="md">Salvar veterinário</Button>
                        <Button size="sm" variant="secondary">
                            + Novo tutor
                        </Button>
                    </div>
                </Secao>

                <SelectExamples />

                <Secao id="formulario" title="2 · Formulário">
                    <FormSection title="Contato">
                        <FormField id="v-nome" label="Nome completo">
                            <TextInput placeholder="Nome do tutor" />
                        </FormField>
                        <FormField id="v-cel" label="Celular" error="Use DDD + número, com 10 ou 11 dígitos.">
                            <TextInput mono defaultValue="(19) 9973" />
                        </FormField>
                        <FormField id="v-cpf" label="CPF" optional hint="Só para nota fiscal.">
                            <TextInput mono placeholder="000.000.000-00" />
                        </FormField>
                        <FormField id="v-min" label="Mínimo · Cão adulto (rótulo só para leitor de tela: hideLabel)" hideLabel>
                            <TextInput mono inputMode="decimal" suffix="mg/dL" placeholder="0,5" />
                        </FormField>
                        <FormField id="v-peso" label="Peso">
                            <TextInput mono defaultValue="12,4" suffix="kg" />
                        </FormField>
                        <FormField id="v-busca" label="Buscar tutor ou animal">
                            <TextInput icon={Search} placeholder="Telefone, CPF ou nome do animal" />
                        </FormField>
                        <FormField id="v-lab" label="Laboratório">
                            <TextInput disabled defaultValue="Laboratório Alfa Vet" />
                        </FormField>
                    </FormSection>

                    <div className="grid gap-6 md:grid-cols-2">
                        <div className="flex flex-col gap-2">
                            <Checkbox id="exemplo-laudo-email" label="Um e-mail com o PDF a cada laudo liberado" defaultChecked />
                            <Checkbox
                                id="exemplo-autorizacao-email"
                                label="Fernanda autoriza receber os laudos por e-mail"
                                description="Registrado com data, hora e atendente."
                            />
                            <Switch checked={acesso} onCheckedChange={setAcesso} label="Acesso ao Vidra" />
                        </div>
                        <RadioCardGroup
                            name="pagador"
                            label="Quem paga os pedidos, por padrão"
                            value={pagador}
                            onChange={setPagador}
                            options={[
                                {
                                    value: 'clinica',
                                    label: 'A clínica, na fatura mensal',
                                    description: 'O exame entra na fatura da clínica escolhida no pedido.',
                                },
                                { value: 'tutor', label: 'O tutor, no balcão', description: 'Paga na entrega da amostra, por Pix ou cartão.' },
                                {
                                    value: 'indisponivel',
                                    label: 'Em breve',
                                    description: <span>Opção indisponível para seleção.</span>,
                                    disabled: true,
                                },
                            ]}
                        />
                    </div>

                    <div className="flex flex-wrap items-start gap-8">
                        <Exemplo nome="ChipToggleGroup mode=multiple">
                            <ChipToggleGroup
                                mode="multiple"
                                label="Locais de atendimento"
                                value={locais}
                                onChange={setLocais}
                                options={[
                                    { value: 'vetcare', label: 'VetCare Jardins' },
                                    { value: 'patas', label: 'Clínica Patas' },
                                    { value: 'domicilio', label: 'Atendimento domiciliar' },
                                ]}
                            />
                        </Exemplo>
                        <Exemplo nome="ChipToggleGroup mode=single">
                            <ChipToggleGroup
                                mode="single"
                                label="Espécie"
                                value={especie}
                                onChange={setEspecie}
                                options={[
                                    { value: 'cao', label: 'Cão' },
                                    { value: 'gato', label: 'Gato' },
                                    { value: 'ave', label: 'Ave' },
                                ]}
                            />
                        </Exemplo>
                        <Exemplo nome="SegmentedControl">
                            <SegmentedControl
                                label="Prioridade"
                                value={prioridade}
                                onChange={setPrioridade}
                                options={[
                                    { value: 'rotina', label: 'Rotina' },
                                    { value: 'urgente', label: 'Urgente' },
                                ]}
                            />
                        </Exemplo>
                    </div>

                    <Exemplo nome="ScanField · digite A-0412-1 (existe) ou A-9999-1 (não existe) e Enter">
                        <ScanField onScan={lerAmostra} state={scan.state} message={scan.message} className="max-w-xl" />
                    </Exemplo>

                    <FormActions secondary={<Button variant="secondary">Cancelar</Button>}>
                        <Button variant="ghost">Salvar rascunho</Button>
                        <Button>Salvar e imprimir 2 etiquetas</Button>
                    </FormActions>
                </Secao>

                <Secao id="status" title="3 · Status e feedback">
                    <div className="flex flex-wrap items-center gap-3">
                        <StatusBadge tone="ok">Entregue</StatusBadge>
                        <StatusBadge tone="warn">Aguardando liberação</StatusBadge>
                        <StatusBadge tone="crit">Valor crítico</StatusBadge>
                        <StatusBadge tone="info">Em bancada</StatusBadge>
                        <StatusBadge tone="neutral">Amostra a caminho</StatusBadge>
                        <ProductBadge line="pet" />
                        <ProductBadge line="clinica" />
                        <AiBadge />
                        <CountPill label="Urgentes" count={5} active />
                        <CountPill label="Atrasados" count={3} />
                    </div>
                    <div className="flex flex-col gap-3">
                        <Alert tone="crit" title="Potássio 7,2 mEq/L no Thor (cão)." action={<Button variant="secondary">Registrar aviso</Button>}>
                            Avise a Clínica Patas antes de liberar o laudo.
                        </Alert>
                        <Alert tone="warn" title="Amostra 0412 hemolisada.">
                            Peça nova coleta ao Dr. Rafael Lima.
                        </Alert>
                        <Alert tone="info" title="Lab Beta Diagnóstico liberou 2 laudos para você." />
                        <Alert tone="ok" title="Laudo do Thor liberado e assinado." />
                    </div>
                    <div className="flex flex-wrap items-start gap-8">
                        <Toast message="Veterinário cadastrado." actionLabel="Desfazer" onAction={() => undefined} onDismiss={() => undefined} />
                        <EmptyState
                            icon={Inbox}
                            title="Nenhum pedido aguardando liberação"
                            description="Quando a bancada terminar um exame, ele aparece aqui para a responsável técnica revisar."
                            action={
                                <Button variant="secondary" size="sm">
                                    Ver a fila da bancada
                                </Button>
                            }
                        />
                    </div>
                </Secao>

                <Secao id="dados" title="4 · Dados e resultados">
                    <div className="grid gap-4 md:grid-cols-3">
                        <MetricCard label="Pedidos hoje" value={48} footnote="31 de 6 clínicas parceiras" />
                        <MetricCard label="Aguardando liberação" value={9} footnote="3 passaram do prazo" tone="warn" />
                        <MetricCard label="Valores críticos em aberto" value={2} footnote="Avisar a clínica antes de liberar" tone="crit" />
                    </div>
                    <DataTable
                        caption="Fila de pedidos de hoje"
                        columns={COLUNAS}
                        rows={PEDIDOS}
                        getRowId={(p) => p.protocolo}
                        selectedId={selecionado}
                        rowTone={(p) => (p.critico ? 'critical' : undefined)}
                        toolbar={
                            <div className="flex flex-wrap items-center justify-between gap-3 p-4">
                                <span className="font-semibold">Fila de hoje</span>
                                <div className="flex gap-2">
                                    {PEDIDOS.map((p) => (
                                        <Button key={p.protocolo} size="sm" variant="ghost" onClick={() => setSelecionado(p.protocolo)}>
                                            {p.protocolo}
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        }
                    />
                    <ResultTable caption="Bioquímico do Thor" referenceLabel="Referência (cão adulto)" rows={RESULTADOS} />
                    <div className="flex flex-wrap items-start gap-12">
                        <DetailList
                            items={[
                                { term: 'CRMV', value: 'CRMV-SP 41208', mono: true },
                                { term: 'E-mail', value: 'rafael.lima@clinicapatas.com.br' },
                                { term: 'Quem paga', value: 'Clínica Patas, na fatura mensal' },
                            ]}
                        />
                        <ProgressBar label="Bioquímica" value={64} display="3h05" footnote="Meta: 4h em rotina" />
                    </div>
                </Secao>

                <Secao id="navegacao" title="5 · Navegação e estrutura">
                    <div className="max-w-sm">
                        <LabSwitcher labs={LABS} currentId={lab} onSelect={setLab} allLabsHref="#" />
                    </div>
                    <Tabs
                        id="pedido"
                        label="Seções do pedido"
                        value={aba}
                        onChange={setAba}
                        tabs={[
                            { value: 'exames', label: 'Exames' },
                            { value: 'amostras', label: 'Amostras' },
                            { value: 'historico', label: 'Histórico do animal' },
                        ]}
                    />
                    <TabPanel tabsId="pedido" value={aba} className="text-muted-foreground">
                        Conteúdo da aba “{aba}”.
                    </TabPanel>
                    <StatusStepper
                        label="Andamento do pedido P-00412"
                        steps={[
                            { label: 'Pedido', time: '08:02', status: 'done' },
                            { label: 'Amostra recebida', time: '08:40', status: 'done' },
                            { label: 'Em bancada', time: '09:15', status: 'done' },
                            { label: 'Liberação', time: 'agora', status: 'current' },
                            { label: 'Entregue', status: 'todo' },
                        ]}
                    />
                    <div className="grid gap-4 md:grid-cols-2">
                        <Card
                            title="Valores críticos"
                            action={
                                <Button variant="ghost" size="sm">
                                    Ver todos
                                </Button>
                            }
                        >
                            <Alert tone="crit" title="Thor · Potássio 7,2 mEq/L" />
                        </Card>
                        <Card title="Liberação">
                            <Button onClick={() => setDialogo(true)}>Liberar laudo do Thor</Button>
                        </Card>
                    </div>
                    <ConfirmDialog
                        open={dialogo}
                        onOpenChange={setDialogo}
                        title="Liberar o laudo do Thor?"
                        description="O laudo será assinado com o certificado da Dra. Marina Costa e enviado por e-mail para Carla Mendes e para o Dr. Rafael Lima."
                        confirmLabel="Liberar e assinar"
                        onConfirm={() => setDialogo(false)}
                    >
                        <Alert tone="crit" title="Há 1 valor crítico.">
                            O aviso à clínica foi registrado às 11:46.
                        </Alert>
                    </ConfirmDialog>
                </Secao>

                <Secao id="entidades" title="6 · Entidades do laboratório">
                    <div className="grid gap-4 md:grid-cols-2">
                        <AnimalCard name="Amora" summary="Gata · SRD · fêmea · 3 anos · 4,1 kg" onSelect={() => undefined} />
                        <AnimalCard
                            name="Thor"
                            summary="Cão · Golden Retriever · macho · 7 anos · 32 kg"
                            selected
                            alert="Creatinina subiu de 1,1 para 2,4 mg/dL entre 12/08 e 04/11."
                        />
                    </div>
                    <div className="bg-card border-border max-w-3xl overflow-hidden rounded-lg border">
                        <PersonRow name="Dr. Rafael Lima" subtitle="Clínica Patas · Clínica geral" registry="CRMV-SP 41208" access="active" />
                        <PersonRow name="Dr. Paulo Viana" subtitle="Hospital Vet Bicho Feliz · Cirurgia" registry="CRMV-SP 60415" access="invited" />
                        <PersonRow name="Dr. Marcos Souza" subtitle="Atendimento domiciliar" registry="CRMV-SP 29654" access="none" />
                    </div>
                    <SampleLabel
                        animal="THOR · cão"
                        owner="Carla Mendes · Clínica Patas"
                        code="A-0412-1"
                        collectedAt="04/11/26 08:12"
                        material="Soro"
                        capColor="#C0392B"
                    />
                </Secao>
            </main>
        </div>
    );
}

const SELECT_UFS = [
    ['AC', 'Acre'],
    ['AL', 'Alagoas'],
    ['AP', 'Amapá'],
    ['AM', 'Amazonas'],
    ['BA', 'Bahia'],
    ['CE', 'Ceará'],
    ['DF', 'Distrito Federal'],
    ['ES', 'Espírito Santo'],
    ['GO', 'Goiás'],
    ['MA', 'Maranhão'],
    ['MT', 'Mato Grosso'],
    ['MS', 'Mato Grosso do Sul'],
    ['MG', 'Minas Gerais'],
    ['PA', 'Pará'],
    ['PB', 'Paraíba'],
    ['PR', 'Paraná'],
    ['PE', 'Pernambuco'],
    ['PI', 'Piauí'],
    ['RJ', 'Rio de Janeiro'],
    ['RN', 'Rio Grande do Norte'],
    ['RS', 'Rio Grande do Sul'],
    ['RO', 'Rondônia'],
    ['RR', 'Roraima'],
    ['SC', 'Santa Catarina'],
    ['SP', 'São Paulo'],
    ['SE', 'Sergipe'],
    ['TO', 'Tocantins'],
].map(([code, label]) => ({ value: code, code, label }));
const SELECT_ESPECIES = ['Cão', 'Gato', 'Equino', 'Bovino', 'Ave', 'Silvestre'].map((label) => ({ value: label, label }));

function SelectExamples() {
    const [uf, setUf] = useState('PB');
    const [especie, setEspecie] = useState('');
    const [clinica, setClinica] = useState('vetcare');
    const [vazio, setVazio] = useState('');
    const [preenchido, setPreenchido] = useState('SP');
    const [erro, setErro] = useState('');
    const [cadastro, setCadastro] = useState(false);
    const [nome, setNome] = useState('');
    const [clinicas, setClinicas] = useState([
        { value: 'vetcare', label: 'VetCare Jardins', description: 'Fatura mensal · fecha dia 5' },
        { value: 'patas', label: 'Clínica Patas', description: 'Fatura mensal · fecha dia 10' },
        { value: 'tutor', label: 'O tutor paga no balcão', description: 'Pix ou cartão na entrega da amostra' },
        { value: 'inativa', label: 'Clínica inativa', description: 'Indisponível para novos pedidos', disabled: true },
    ]);
    return (
        <Secao id="select" title="Select">
            <p className="text-muted-foreground text-sm">
                Busque por código ou nome da UF. Use as setas para navegar e Enter para escolher. No celular, a lista abre em uma folha.
            </p>
            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <FormField id="v-select-uf" label="UF" hint="Busca automática · 27 opções">
                    <Select value={uf} onChange={setUf} name="uf" options={SELECT_UFS} searchable searchPlaceholder="Buscar UF ou estado" />
                </FormField>
                <FormField id="v-select-especie" label="Espécie" hint="Lista simples, sem busca">
                    <Select value={especie} onChange={setEspecie} options={SELECT_ESPECIES} placeholder="Selecione a espécie" />
                </FormField>
                <FormField id="v-select-clinica" label="Clínica que paga">
                    <Select
                        value={clinica}
                        onChange={setClinica}
                        options={clinicas}
                        footer={
                            <Button variant="ghost" onClick={() => setCadastro(true)}>
                                + Cadastrar outra clínica
                            </Button>
                        }
                    />
                </FormField>
            </div>
            {cadastro && (
                <form
                    className="flex flex-wrap items-end gap-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        if (!nome.trim()) return;
                        const value = `clinica-${clinicas.length}`;
                        setClinicas([...clinicas, { value, label: nome.trim(), description: 'Clínica de demonstração' }]);
                        setClinica(value);
                        setNome('');
                        setCadastro(false);
                    }}
                >
                    <FormField id="v-nova-clinica" label="Nome da clínica">
                        <TextInput value={nome} onChange={(event) => setNome(event.target.value)} required />
                    </FormField>
                    <Button type="submit">Cadastrar clínica</Button>
                    <Button variant="ghost" onClick={() => setCadastro(false)}>
                        Cancelar
                    </Button>
                </form>
            )}
            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <FormField id="v-select-vazio" label="UF · vazio">
                    <Select value={vazio} onChange={setVazio} options={SELECT_UFS} />
                </FormField>
                <FormField id="v-select-preenchido" label="UF · preenchido">
                    <Select value={preenchido} onChange={setPreenchido} options={SELECT_UFS} />
                </FormField>
                <FormField id="v-select-erro" label="UF · erro" error={!erro ? 'Escolha a UF.' : undefined}>
                    <Select value={erro} onChange={setErro} options={SELECT_UFS} />
                </FormField>
                <FormField id="v-select-desabilitado" label="UF · desabilitado">
                    <Select value="SP" onChange={() => {}} options={SELECT_UFS} disabled />
                </FormField>
            </div>
        </Secao>
    );
}
