import { Button, ChipToggleGroup, DataTable, EmptyState, FormField, PageHeader, StatusBadge, TextInput, useFlashToast } from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { ClipboardList, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { tomDaSituacao } from './formato';

type Pedido = {
    id: string;
    protocolo: string;
    pacienteNome: string;
    especieCurta: string;
    solicitanteNome: string;
    exames: string[];
    situacao: string;
    situacaoRotulo: string;
    urgente: boolean;
    criadoEm: string;
};
type Filtros = { data: string; situacao: string; busca: string };
type Props = { pedidos: Pedido[]; filtros: Filtros; situacoes: { value: string; label: string }[] };

export default function Pedidos({ pedidos, filtros, situacoes }: Props) {
    const [busca, setBusca] = useState(filtros.busca);
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    useFlashToast();
    useEffect(
        () => () => {
            if (timer.current) clearTimeout(timer.current);
        },
        [],
    );

    const filtrar = (novos: Partial<Filtros>) => {
        router.get(route('lab.pedidos.index'), { ...filtros, ...novos }, { preserveState: true, preserveScroll: true, replace: true });
    };
    const digitar = (texto: string) => {
        setBusca(texto);
        if (timer.current) clearTimeout(timer.current);
        timer.current = setTimeout(() => filtrar({ busca: texto }), 350);
    };
    const temFiltro = filtros.situacao !== '' || filtros.busca !== '';

    return (
        <AppLayout breadcrumbs={[{ title: 'Pedidos', href: route('lab.pedidos.index') }]}>
            <Head title="Pedidos" />
            <div className="min-w-0 space-y-6 p-4 md:p-8">
                <PageHeader
                    eyebrow="Rotina"
                    title="Pedidos"
                    description="Os pedidos do dia escolhido, do mais novo para o mais antigo."
                    actions={
                        <Button asChild>
                            <Link href={route('lab.pedidos.create')}>Novo pedido</Link>
                        </Button>
                    }
                />
                <div className="grid items-end gap-4 md:grid-cols-[minmax(0,1fr)_200px]">
                    <FormField id="busca" label="Buscar pedido">
                        <TextInput
                            icon={Search}
                            value={busca}
                            onChange={(e) => digitar(e.target.value)}
                            placeholder="Protocolo, paciente ou responsável"
                        />
                    </FormField>
                    <FormField id="data" label="Dia">
                        <TextInput type="date" value={filtros.data} onChange={(e) => e.target.value && filtrar({ data: e.target.value })} />
                    </FormField>
                </div>
                <ChipToggleGroup
                    mode="single"
                    label="Situação do pedido"
                    value={filtros.situacao}
                    onChange={(situacao) => filtrar({ situacao })}
                    options={[{ value: '', label: 'Todos' }, ...situacoes]}
                />
                <DataTable
                    caption="Pedidos do dia"
                    rows={pedidos}
                    getRowId={(p) => p.id}
                    columns={[
                        {
                            key: 'protocolo',
                            header: 'Protocolo',
                            mono: true,
                            cell: (p) => (
                                <Link
                                    href={route('lab.pedidos.show', { pedido: p.id })}
                                    className="text-brand-500 font-semibold underline-offset-2 hover:underline"
                                >
                                    {p.protocolo}
                                </Link>
                            ),
                        },
                        {
                            key: 'paciente',
                            header: 'Paciente',
                            cell: (p) => (
                                <>
                                    <strong>{p.pacienteNome}</strong> <span className="text-muted-foreground">· {p.especieCurta}</span>
                                </>
                            ),
                        },
                        { key: 'solicitante', header: 'Solicitante', cell: (p) => p.solicitanteNome },
                        { key: 'exames', header: 'Exames', mono: true, cell: (p) => p.exames.join(', ') },
                        {
                            key: 'situacao',
                            header: 'Situação',
                            cell: (p) => (
                                <span className="flex flex-wrap items-center gap-1.5">
                                    <StatusBadge tone={tomDaSituacao[p.situacao] ?? 'neutral'}>{p.situacaoRotulo}</StatusBadge>
                                    {p.urgente && <StatusBadge tone="crit">Urgente</StatusBadge>}
                                </span>
                            ),
                        },
                        { key: 'hora', header: 'Hora', mono: true, cell: (p) => p.criadoEm },
                    ]}
                    emptyState={
                        <EmptyState
                            icon={ClipboardList}
                            title={temFiltro ? 'Nenhum pedido encontrado' : 'Nenhum pedido neste dia'}
                            description={
                                temFiltro
                                    ? 'Tire um filtro ou tente outro protocolo ou nome.'
                                    : 'Abra o primeiro pedido do dia quando a amostra chegar.'
                            }
                            action={
                                <Button asChild>
                                    <Link href={route('lab.pedidos.create')}>Novo pedido</Link>
                                </Button>
                            }
                        />
                    }
                />
            </div>
        </AppLayout>
    );
}
