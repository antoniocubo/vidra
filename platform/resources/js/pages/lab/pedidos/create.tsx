import {
    Alert,
    Button,
    Card,
    FormActions,
    FormField,
    FormSection,
    IconButton,
    PageHeader,
    RadioCardGroup,
    SegmentedControl,
    Select,
    TextInput,
} from '@/components/vidra';
import { AnimalCard } from '@/components/vidra/pet';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { BuscaDeExame, type Exame } from './busca-de-exame';
import { comDesconto, reais } from './formato';

type Clinica = { id: string; nome: string; descontoPercentual: number };
type Solicitante = { id: string; nome: string; registro: string; pagadorPadrao: string; clinicas: Clinica[] };
type Paciente = {
    id: string;
    nome: string;
    descricao: string;
    especieCurta: string;
    responsavelNome: string;
    autorizaEmail: boolean;
    responsavelTelefone: string;
};
type Textos = {
    paciente: string;
    responsavel: string;
    solicitante: string;
    secaoDoPaciente: string;
    buscaDoPaciente: string;
    pagadorSolicitante: string;
    pagadorResponsavel: string;
    novoSolicitante: string;
    novoPaciente: string;
};
type Props = {
    solicitantes: Solicitante[];
    clinicasAtivas: Clinica[];
    pacienteInicial: Paciente | null;
    textos: Textos;
    pacientes: Paciente[];
    exames: Exame[];
    links: { novoSolicitante: string; novoPaciente: string };
};
type Pagador = 'clinica' | 'solicitante' | 'responsavel';

const DEBOUNCE_MS = 350;

/** Busca no servidor sem perder o que já foi preenchido: só a prop pedida é recarregada. */
function useBuscaNoServidor(parametro: string, prop: 'pacientes' | 'exames') {
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    useEffect(
        () => () => {
            if (timer.current) clearTimeout(timer.current);
        },
        [],
    );

    return (termo: string) => {
        if (timer.current) clearTimeout(timer.current);
        if (termo.trim() === '') return;
        timer.current = setTimeout(
            () =>
                router.get(
                    route('lab.pedidos.create'),
                    { ...Object.fromEntries(new URLSearchParams(window.location.search)), [parametro]: termo },
                    { only: [prop], preserveState: true, preserveScroll: true, replace: true },
                ),
            DEBOUNCE_MS,
        );
    };
}

const materialDe = (exame: Exame) => exame.material.trim().toLowerCase();

export default function NovoPedido({ solicitantes, clinicasAtivas, pacienteInicial, textos, pacientes, exames, links }: Props) {
    const form = useForm({
        paciente_id: pacienteInicial?.id ?? '',
        solicitante_id: '',
        pagador: 'clinica' as Pagador,
        clinica_id: '',
        prioridade: 'rotina',
        exame_ids: [] as string[],
    });
    const erros: Record<string, string | undefined> = form.errors;
    const [paciente, setPaciente] = useState<Paciente | null>(pacienteInicial);
    const [escolhidos, setEscolhidos] = useState<Exame[]>([]);
    const [buscaPaciente, setBuscaPaciente] = useState('');
    const buscarPacientes = useBuscaNoServidor('busca_paciente', 'pacientes');

    const solicitante = solicitantes.find((s) => s.id === form.data.solicitante_id) ?? null;
    const clinicas = solicitante?.clinicas ?? clinicasAtivas;
    const clinica = form.data.pagador === 'clinica' ? (clinicas.find((c) => c.id === form.data.clinica_id) ?? null) : null;
    const desconto = clinica?.descontoPercentual ?? 0;
    const total = escolhidos.reduce((soma, e) => soma + comDesconto(e.precoCentavos, desconto), 0);
    const etiquetas = new Set(escolhidos.map(materialDe)).size;

    const escolherSolicitante = (id: string) => {
        const escolhido = solicitantes.find((s) => s.id === id);
        const pagador = (escolhido?.pagadorPadrao ?? 'clinica') as Pagador;
        form.setData({ ...form.data, solicitante_id: id, pagador, clinica_id: pagador === 'clinica' ? (escolhido?.clinicas[0]?.id ?? '') : '' });
    };
    const escolherPagador = (pagador: Pagador) =>
        form.setData({ ...form.data, pagador, clinica_id: pagador === 'clinica' ? (clinicas[0]?.id ?? '') : '' });
    const escolherPaciente = (escolhido: Paciente) => {
        setPaciente(escolhido);
        form.setData('paciente_id', escolhido.id);
    };
    const adicionarExame = (exame: Exame) => {
        if (!form.data.exame_ids.includes(exame.id)) {
            setEscolhidos([...escolhidos, exame]);
            form.setData('exame_ids', [...form.data.exame_ids, exame.id]);
        }
    };
    const removerExame = (id: string) => {
        setEscolhidos(escolhidos.filter((e) => e.id !== id));
        form.setData(
            'exame_ids',
            form.data.exame_ids.filter((x) => x !== id),
        );
    };

    const textoDoTotal =
        form.data.pagador === 'clinica'
            ? `Total na tabela ${clinica ? `da ${clinica.nome}` : 'da clínica'} · fatura do mês`
            : form.data.pagador === 'solicitante'
              ? `Total para ${textos.pagadorSolicitante.toLowerCase()} · fatura do mês`
              : 'Total para pagar no balcão';
    const textoDoBotao = etiquetas === 0 ? 'Salvar pedido' : `Salvar e imprimir ${etiquetas} ${etiquetas === 1 ? 'etiqueta' : 'etiquetas'}`;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Pedidos', href: route('lab.pedidos.index') },
                { title: 'Novo pedido', href: route('lab.pedidos.create') },
            ]}
        >
            <Head title="Novo pedido" />
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(route('lab.pedidos.store'));
                }}
                className="flex max-w-4xl flex-col gap-4 p-4 md:p-8"
            >
                <PageHeader back={{ href: route('lab.pedidos.index'), label: 'Pedidos' }} eyebrow="Rotina" title="Novo pedido" />

                <FormSection title="Quem pede">
                    <FormField id="solicitante" label={textos.solicitante} error={erros.solicitante_id} className="sm:col-span-2">
                        <Select
                            value={form.data.solicitante_id}
                            onChange={escolherSolicitante}
                            searchable
                            placeholder="Escolha quem pede os exames"
                            options={solicitantes.map((s) => ({ value: s.id, label: s.nome, description: s.registro }))}
                        />
                    </FormField>
                    <Link href={links.novoSolicitante} className="text-brand-500 -mt-2 w-fit text-sm font-semibold hover:underline sm:col-span-2">
                        {textos.novoSolicitante}
                    </Link>
                    <div className="flex flex-col gap-3 sm:col-span-2">
                        <RadioCardGroup
                            name="pagador"
                            label="Quem paga"
                            value={form.data.pagador}
                            onChange={escolherPagador}
                            options={[
                                { value: 'clinica', label: 'Clínica', description: 'Entra na fatura mensal, com o desconto dela.' },
                                { value: 'solicitante', label: textos.pagadorSolicitante, description: 'Entra na fatura mensal dele.' },
                                {
                                    value: 'responsavel',
                                    label: `${textos.pagadorResponsavel} no balcão`,
                                    description: 'Paga na hora, pela tabela do laboratório.',
                                },
                            ]}
                        />
                        {form.data.pagador === 'clinica' && (
                            <FormField
                                id="clinica"
                                label="Clínica que recebe a fatura"
                                error={erros.clinica_id}
                                hint={
                                    solicitante && solicitante.clinicas.length === clinicasAtivas.length
                                        ? 'Ele não tem clínica própria: mostramos todas as ativas.'
                                        : undefined
                                }
                            >
                                <Select
                                    value={form.data.clinica_id}
                                    onChange={(id) => form.setData('clinica_id', id)}
                                    placeholder="Escolha a clínica"
                                    options={clinicas.map((c) => ({
                                        value: c.id,
                                        label: c.nome,
                                        description: c.descontoPercentual > 0 ? `${c.descontoPercentual}% de desconto` : 'Tabela do laboratório',
                                    }))}
                                />
                            </FormField>
                        )}
                    </div>
                </FormSection>

                <Card title={textos.secaoDoPaciente}>
                    <FormField id="busca-paciente" label={textos.buscaDoPaciente} error={erros.paciente_id}>
                        <TextInput
                            icon={Search}
                            value={buscaPaciente}
                            onChange={(e) => {
                                setBuscaPaciente(e.target.value);
                                buscarPacientes(e.target.value);
                            }}
                            autoComplete="off"
                        />
                    </FormField>
                    {buscaPaciente.trim() !== '' && (
                        <div className="grid gap-3 sm:grid-cols-2">
                            {pacientes.map((p) => (
                                <AnimalCard
                                    key={p.id}
                                    name={p.nome}
                                    summary={p.descricao}
                                    selected={paciente?.id === p.id}
                                    onSelect={() => escolherPaciente(p)}
                                />
                            ))}
                            {pacientes.length === 0 && <p className="text-muted-foreground text-sm">Nenhum resultado para essa busca.</p>}
                        </div>
                    )}
                    {paciente && (
                        <div className="bg-secondary flex flex-wrap items-center justify-between gap-2 rounded-lg px-4 py-3 text-sm">
                            <span>
                                <strong>{paciente.nome}</strong> · {textos.responsavel}: {paciente.responsavelNome} ·{' '}
                                {paciente.autorizaEmail ? 'recebe laudos por e-mail' : 'sem autorização para laudo por e-mail'}
                            </span>
                            <span className="text-muted-foreground font-mono text-xs">{paciente.responsavelTelefone}</span>
                        </div>
                    )}
                    <Link href={links.novoPaciente} className="text-brand-500 w-fit text-sm font-semibold hover:underline">
                        {textos.novoPaciente}
                    </Link>
                </Card>

                <Card
                    title="Exames"
                    action={
                        <SegmentedControl
                            label="Prioridade"
                            value={form.data.prioridade}
                            onChange={(prioridade) => form.setData('prioridade', prioridade)}
                            options={[
                                { value: 'rotina', label: 'Rotina' },
                                { value: 'urgente', label: 'Urgente' },
                            ]}
                        />
                    }
                >
                    <BuscaDeExame
                        exames={exames}
                        desconto={desconto}
                        escolhidos={form.data.exame_ids}
                        onEscolher={adicionarExame}
                        error={erros.exame_ids ?? erros['exame_ids.0']}
                    />
                    {escolhidos.length === 0 ? (
                        <p className="text-muted-foreground text-sm">Nenhum exame ainda. Digite acima para adicionar.</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <caption className="sr-only">Exames do pedido</caption>
                                <thead className="text-muted-foreground border-border border-b text-xs">
                                    <tr>
                                        <th className="p-3">Exame</th>
                                        <th className="p-3">Material</th>
                                        <th className="p-3 text-right">Preço</th>
                                        <th className="p-3">
                                            <span className="sr-only">Remover</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {escolhidos.map((e) => (
                                        <tr key={e.id} className="border-border border-b">
                                            <td className="p-3">
                                                <span className="font-mono">{e.codigo}</span> · {e.nome}
                                            </td>
                                            <td className="p-3">{e.material}</td>
                                            <td className="p-3 text-right font-mono tabular-nums">{reais(comDesconto(e.precoCentavos, desconto))}</td>
                                            <td className="p-1">
                                                <IconButton icon={X} label={`Remover ${e.nome}`} variant="ghost" onClick={() => removerExame(e.id)} />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot>
                                    <tr className="font-semibold">
                                        <td className="p-3" colSpan={2}>
                                            {textoDoTotal}
                                        </td>
                                        <td className="p-3 text-right font-mono tabular-nums">{reais(total)}</td>
                                        <td />
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    )}
                </Card>

                {erros.form && <Alert tone="crit" title={erros.form} />}
                <FormActions
                    secondary={
                        <Button variant="secondary" asChild>
                            <Link href={route('lab.pedidos.index')}>Cancelar pedido</Link>
                        </Button>
                    }
                >
                    <Button type="submit" loading={form.processing}>
                        {textoDoBotao}
                    </Button>
                </FormActions>
            </form>
        </AppLayout>
    );
}
