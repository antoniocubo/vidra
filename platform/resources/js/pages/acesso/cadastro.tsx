import {
    Alert,
    Button,
    Checkbox,
    FormActions,
    FormField,
    FormSection,
    ProgressBar,
    RadioCardGroup,
    SegmentedControl,
    Select,
    StatusBadge,
    StatusStepper,
    TextInput,
    VidraLogo,
} from '@/components/vidra';
import { Head, Link, useForm } from '@inertiajs/react';
import { useRef, useState, type FormEvent, type InputHTMLAttributes } from 'react';

type Plano = { value: string; nome: string; preco: number; pedidos: number; usuarios: number | null; unidades: number };
type Props = { ufs: { value: string; nome: string }[]; planos: Plano[]; planoInicial: string | null; cobrancaInicial: string | null };
const inicial = {
    laboratorio_nome: '',
    cnpj: '',
    telefone: '',
    cep: '',
    cidade: '',
    uf: '',
    endereco: '',
    linha: 'pet',
    sou_rt: true,
    rt_nome: '',
    rt_numero: '',
    rt_uf: '',
    rt_titulo: '',
    rt_email: '',
    nome: '',
    email: '',
    celular: '',
    senha: '',
    termos: false,
    plano: 'laboratorio',
    cobranca: 'mensal',
};
type Campo = keyof typeof inicial;
type CampoTexto = Exclude<Campo, 'sou_rt' | 'termos'>;
const etapas = ['Laboratório', 'Responsável técnico', 'Seu acesso', 'Plano'];
const campos: Campo[][] = [
    ['linha', 'laboratorio_nome', 'cnpj', 'telefone', 'cep', 'cidade', 'uf', 'endereco'],
    ['sou_rt', 'rt_nome', 'rt_numero', 'rt_uf', 'rt_titulo', 'rt_email'],
    ['nome', 'email', 'celular', 'senha', 'termos'],
    ['plano', 'cobranca'],
];
const proximos = ['Continuar para o responsável técnico', 'Continuar para o seu acesso', 'Escolher o plano'];
const numero = (valor: number) => valor.toLocaleString('pt-BR');

export default function Cadastro({ ufs, planos, planoInicial, cobrancaInicial }: Props) {
    const [etapa, setEtapa] = useState(0);
    const form = useRef<HTMLFormElement>(null);
    const titulo = useRef<HTMLHeadingElement>(null);
    const { data, setData, post, errors, processing, setError, clearErrors } = useForm({
        ...inicial,
        plano: planoInicial ?? inicial.plano,
        cobranca: cobrancaInicial ?? inicial.cobranca,
    });
    const forca =
        data.senha.length < 10
            ? 0
            : [
                  data.senha.length >= 14,
                  /[A-Z]/.test(data.senha) && /[a-z]/.test(data.senha),
                  /\d/.test(data.senha),
                  /[^a-zA-Z0-9]/.test(data.senha),
              ].filter(Boolean).length;
    const mudarEtapa = (nova: number) => {
        setEtapa(nova);
        requestAnimationFrame(() => titulo.current?.focus());
    };
    const salvar = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (etapa < 3) {
            if (!form.current?.reportValidity()) return;
            const ufObrigatoria = etapa === 0 ? 'uf' : etapa === 1 ? 'rt_uf' : null;
            if (ufObrigatoria && !data[ufObrigatoria]) {
                setError(ufObrigatoria, 'Escolha a UF.');
                document.getElementById(ufObrigatoria)?.focus();
                return;
            }
            if (etapa === 1 && data.sou_rt && !data.nome) setData('nome', data.rt_nome);
            mudarEtapa(etapa + 1);
            return;
        }
        post(route('cadastro.store'), {
            onError: (erros) => {
                const primeira = campos.findIndex((grupo) => grupo.some((campo) => campo in erros));
                mudarEtapa(primeira < 0 ? 0 : primeira);
                requestAnimationFrame(() => form.current?.querySelector<HTMLElement>('[aria-invalid="true"]')?.focus());
            },
        });
    };
    const campo = (
        nome: CampoTexto,
        label: string,
        props: InputHTMLAttributes<HTMLInputElement> & { mono?: boolean; wide?: boolean; optional?: boolean } = {},
    ) => {
        const { wide, optional, ...input } = props;
        return (
            <FormField id={nome} label={label} error={errors[nome]} optional={optional} className={wide ? 'sm:col-span-2' : undefined}>
                <TextInput
                    name={nome}
                    value={data[nome]}
                    onChange={(event) => setData(nome, event.target.value)}
                    required={!optional}
                    maxLength={255}
                    {...input}
                />
            </FormField>
        );
    };
    const estado = (nome: 'uf' | 'rt_uf') => (
        <FormField id={nome} label="UF" error={errors[nome]}>
            <Select
                name={nome}
                aria-required="true"
                value={data[nome]}
                onChange={(value) => {
                    setData(nome, value);
                    clearErrors(nome);
                }}
                options={ufs.map((uf) => ({ value: uf.value, code: uf.value, label: uf.nome }))}
                searchPlaceholder="Buscar UF ou estado"
            />
        </FormField>
    );

    return (
        <div className="bg-background text-foreground min-h-screen">
            <Head title="Cadastre seu laboratório" />
            <header className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-6 sm:px-8">
                <Link href="/" aria-label="Vidra — início">
                    <VidraLogo line="marca" className="text-primary h-7" />
                </Link>
                <span className="text-muted-foreground text-sm">
                    Já tem conta?{' '}
                    <Link href={route('login')} className="text-primary font-semibold underline-offset-4 hover:underline">
                        Entrar
                    </Link>
                </span>
            </header>
            <main className="mx-auto flex max-w-6xl flex-col gap-6 px-4 pt-2 pb-12 sm:px-8">
                <div>
                    <h1 ref={titulo} tabIndex={-1} className="font-display text-3xl font-bold outline-none">
                        Cadastre seu laboratório
                    </h1>
                    <p className="text-muted-foreground mt-1">14 dias de teste com todos os recursos. Leva uns 3 minutos.</p>
                </div>
                <StatusStepper
                    label="Etapas do cadastro"
                    steps={etapas.map((label, index) => ({ label, status: index === etapa ? 'current' : index < etapa ? 'done' : 'todo' }))}
                />
                <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                    <form ref={form} onSubmit={salvar} className="flex min-w-0 flex-col gap-4">
                        {Object.keys(errors).length > 0 && <Alert tone="crit" title="Confira os campos indicados para continuar." />}
                        {etapa === 0 && (
                            <FormSection title="O laboratório" description="Estes dados saem no cabeçalho do laudo e na fatura das clínicas.">
                                <RadioCardGroup
                                    name="linha"
                                    label="Que tipo de laboratório?"
                                    value={data.linha}
                                    onChange={(value) => setData('linha', value)}
                                    className="sm:col-span-2"
                                    options={[
                                        { value: 'pet', label: 'Veterinário', description: 'Vidra Pet: tutor, animal, referência por espécie.' },
                                        {
                                            value: 'clinica',
                                            label: 'Análises clínicas humanas',
                                            description: 'Vidra Clínica · em breve',
                                            disabled: true,
                                        },
                                    ]}
                                />
                                {errors.linha && <p className="text-crit-600 text-sm sm:col-span-2">{errors.linha}</p>}
                                {campo('laboratorio_nome', 'Nome do laboratório', {
                                    wide: true,
                                    autoComplete: 'organization',
                                    minLength: 3,
                                    placeholder: 'Laboratório Alfa Vet',
                                })}
                                {campo('cnpj', 'CNPJ', { mono: true, inputMode: 'numeric', maxLength: 18, placeholder: '00.000.000/0000-00' })}
                                {campo('telefone', 'Telefone', {
                                    mono: true,
                                    type: 'tel',
                                    autoComplete: 'tel',
                                    maxLength: 20,
                                    placeholder: '(19) 3242-7180',
                                })}
                                {campo('cep', 'CEP', {
                                    mono: true,
                                    inputMode: 'numeric',
                                    autoComplete: 'postal-code',
                                    pattern: '[0-9]{5}-?[0-9]{3}',
                                    maxLength: 9,
                                })}
                                {campo('cidade', 'Cidade', { autoComplete: 'address-level2' })}
                                {estado('uf')}
                                {campo('endereco', 'Endereço', {
                                    wide: true,
                                    autoComplete: 'street-address',
                                    placeholder: 'Rua, número, complemento e bairro',
                                })}
                            </FormSection>
                        )}
                        {etapa === 1 && (
                            <FormSection
                                title="Responsável técnico"
                                description="O RT é o único que libera e assina laudos. O nome e o CRMV saem no rodapé de todo laudo."
                            >
                                <Checkbox
                                    id="sou_rt"
                                    label="Eu sou o responsável técnico deste laboratório"
                                    checked={data.sou_rt}
                                    onChange={(event) => setData('sou_rt', event.target.checked)}
                                    className="bg-background rounded-md px-3 sm:col-span-2"
                                />
                                {campo('rt_nome', 'Nome completo', { wide: true, minLength: 3 })}
                                {campo('rt_numero', 'CRMV', { mono: true, maxLength: 20 })}
                                {estado('rt_uf')}
                                {campo('rt_titulo', 'Título no laudo', {
                                    optional: true,
                                    wide: true,
                                    placeholder: 'Patologista clínica veterinária',
                                })}
                                {!data.sou_rt &&
                                    campo('rt_email', 'E-mail do RT', { wide: true, type: 'email', placeholder: 'rt@seulaboratorio.com.br' })}
                                {!data.sou_rt && (
                                    <p className="text-muted-foreground text-xs sm:col-span-2">
                                        Guardaremos os dados do RT. O convite para criar o acesso estará disponível depois.
                                    </p>
                                )}
                                <Alert
                                    tone="info"
                                    title="O certificado digital A1 (ICP-Brasil) pode ser enviado depois, em Configurações."
                                    className="sm:col-span-2"
                                >
                                    Durante o teste, os laudos saem com a marca “teste”.
                                </Alert>
                            </FormSection>
                        )}
                        {etapa === 2 && (
                            <FormSection title="Seu acesso" description="Você será o administrador: convida a equipe e cuida do plano.">
                                {campo('nome', 'Seu nome', { wide: true, autoComplete: 'name', minLength: 3 })}
                                {campo('email', 'E-mail (é o seu login)', { type: 'email', autoComplete: 'username' })}
                                {campo('celular', 'Celular', { mono: true, type: 'tel', maxLength: 20 })}
                                {campo('senha', 'Crie uma senha', {
                                    type: 'password',
                                    autoComplete: 'new-password',
                                    minLength: 10,
                                    maxLength: 72,
                                    wide: true,
                                })}
                                <ProgressBar
                                    label="Força da senha"
                                    value={data.senha ? Math.max(15, forca * 25) : 0}
                                    display={forca >= 3 ? 'Senha forte' : forca >= 1 ? 'Senha moderada' : 'Senha fraca'}
                                    footnote="Pelo menos 10 caracteres. Uma frase curta funciona bem."
                                    className="sm:col-span-2"
                                />
                                <div className="sm:col-span-2">
                                    <Checkbox
                                        id="termos"
                                        required
                                        checked={data.termos}
                                        onChange={(event) => setData('termos', event.target.checked)}
                                        label="Li e aceito os termos de uso e a política de privacidade."
                                        description="O laboratório é o controlador dos dados de tutores e animais; o Vidra é o operador (LGPD)."
                                        aria-invalid={!!errors.termos}
                                        aria-describedby={errors.termos ? 'termos-erro' : undefined}
                                    />
                                    {errors.termos && (
                                        <p id="termos-erro" className="text-crit-600 text-xs">
                                            {errors.termos}
                                        </p>
                                    )}
                                </div>
                            </FormSection>
                        )}
                        {etapa === 3 && (
                            <FormSection
                                title="Plano depois do teste"
                                description="Cobrança por pedido, não por exame. Dá para trocar a qualquer momento."
                            >
                                <SegmentedControl
                                    label="Período de cobrança"
                                    value={data.cobranca}
                                    onChange={(value) => setData('cobranca', value)}
                                    options={[
                                        { value: 'mensal', label: 'Mensal' },
                                        { value: 'anual', label: 'Anual · 2 meses grátis' },
                                    ]}
                                    className="w-fit max-w-full sm:col-span-2"
                                />
                                {errors.cobranca && <p className="text-crit-600 text-sm sm:col-span-2">{errors.cobranca}</p>}
                                <RadioCardGroup
                                    name="plano"
                                    label="Escolha seu plano"
                                    value={data.plano}
                                    onChange={(value) => setData('plano', value)}
                                    className="sm:col-span-2 [&_[role=radiogroup]]:sm:grid-cols-3"
                                    options={planos.map((plano) => ({
                                        value: plano.value,
                                        label: plano.nome,
                                        description: (
                                            <span className="flex flex-col gap-2">
                                                {plano.value === 'laboratorio' && <StatusBadge tone="warn">Mais usado</StatusBadge>}
                                                <span className="text-foreground font-display text-2xl font-bold">
                                                    R$ {numero((plano.preco / 100) * (data.cobranca === 'anual' ? 10 : 1))}
                                                </span>
                                                <span>{data.cobranca === 'anual' ? 'por ano' : 'por mês'}</span>
                                                <span className="text-foreground">{numero(plano.pedidos)} pedidos por mês</span>
                                                <span className="text-foreground">
                                                    {plano.usuarios === null ? 'Usuários ilimitados' : `${plano.usuarios} usuários`}
                                                </span>
                                                <span>{plano.unidades === 1 ? '1 unidade' : `Até ${plano.unidades} unidades`}</span>
                                            </span>
                                        ),
                                    }))}
                                />
                                {errors.plano && <p className="text-crit-600 text-sm sm:col-span-2">{errors.plano}</p>}
                                <p className="text-muted-foreground text-xs sm:col-span-2">
                                    Passou da franquia? R$ 0,25 por pedido a mais. Sem taxa de implantação. Clínicas, veterinários e tutores não pagam
                                    nada.
                                </p>
                            </FormSection>
                        )}
                        <FormActions
                            secondary={
                                etapa > 0 ? (
                                    <Button type="button" variant="secondary" disabled={processing} onClick={() => mudarEtapa(etapa - 1)}>
                                        ← Voltar
                                    </Button>
                                ) : (
                                    <Button variant="ghost" asChild>
                                        <Link href={route('login')}>Cancelar</Link>
                                    </Button>
                                )
                            }
                        >
                            <Button type="submit" loading={processing} className="h-auto min-h-11 whitespace-normal">
                                {etapa === 3 ? 'Começar o teste de 14 dias' : proximos[etapa]}
                            </Button>
                        </FormActions>
                    </form>
                    <aside aria-label="Resumo do teste" className="flex min-w-0 flex-col gap-4">
                        <section className="border-border bg-card rounded-lg border p-5">
                            <h2 className="font-semibold">Nos 14 dias de teste</h2>
                            <ul className="mt-3 flex flex-col gap-3 text-sm">
                                {[
                                    'Todos os recursos do plano Laboratório.',
                                    'Catálogo de exames veterinários pronto, com referências por espécie para ajustar.',
                                    'Convide a equipe e os veterinários que pedem exames.',
                                    'No fim, você escolhe o plano. Se não escolher, a conta fica só para consulta.',
                                ].map((texto) => (
                                    <li key={texto} className="flex gap-3">
                                        <span aria-hidden="true" className="text-ok-600 font-bold">
                                            ✓
                                        </span>
                                        {texto}
                                    </li>
                                ))}
                            </ul>
                        </section>
                        <section className="border-border bg-card rounded-lg border p-5">
                            <h2 className="font-semibold">Como vai sair no laudo</h2>
                            <div className="bg-background wrap-anywhere mt-3 flex flex-col gap-1 rounded-md p-3">
                                <p className="text-primary font-bold">{data.laboratorio_nome || 'Nome do laboratório'}</p>
                                <p className="text-muted-foreground text-xs">
                                    {data.endereco || 'Endereço'} · {data.cidade || 'Cidade'}
                                    {data.uf && `, ${data.uf}`}
                                </p>
                                <p className="font-mono text-xs">{data.telefone || 'Telefone'}</p>
                                <p className="text-muted-foreground mt-3 text-xs">Responsável técnico</p>
                                <p className="text-sm font-semibold">{data.rt_nome || 'Nome do responsável'}</p>
                                <p className="font-mono text-xs">
                                    CRMV-{data.rt_uf || 'UF'} {data.rt_numero || 'Número'}
                                </p>
                                {data.rt_titulo && <p className="text-muted-foreground text-xs">{data.rt_titulo}</p>}
                            </div>
                        </section>
                    </aside>
                </div>
            </main>
        </div>
    );
}
