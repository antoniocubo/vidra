import { Button, DataTable, EmptyState, FormField, PageHeader, Select, StatusBadge, TextInput, useFlashToast } from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FileText, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

type Laudo = {
    id: string;
    pedidoId: string;
    numero: string;
    protocolo: string;
    paciente: string;
    solicitante: string;
    liberadoEm: string;
    rt: string;
    retificado: boolean;
    emRetificacao: boolean;
    temValorCritico: boolean;
    emailOk: boolean;
    emailFalhou: boolean;
    acessos: number;
    ultimoAcesso: string | null;
};
type Filtros = { busca: string; de: string; ate: string; entrega: string };
type Props = { laudos: Laudo[]; filtros: Filtros; pagina: number; temProxima: boolean };
const entregas = [
    { value: 'todas', label: 'Todas as entregas' },
    { value: 'entregue', label: 'E-mail entregue' },
    { value: 'falhou', label: 'Falha no e-mail' },
    { value: 'pendente', label: 'E-mail pendente' },
    { value: 'nao_aberto', label: 'Link não aberto' },
];

export default function Laudos({ laudos, filtros, pagina, temProxima }: Props) {
    const areas = usePage<SharedData>().props.laboratorio?.areas ?? [];
    const podeAbrirPedidos = areas.includes('pedidos');
    useFlashToast();
    const [busca, setBusca] = useState(filtros.busca);
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    useEffect(() => setBusca(filtros.busca), [filtros.busca]);
    useEffect(
        () => () => {
            if (timer.current) clearTimeout(timer.current);
        },
        [],
    );
    const filtrar = (novos: Partial<Filtros>, proximaPagina = 1) => {
        if (timer.current) clearTimeout(timer.current);
        router.get(
            route('lab.laudos.index'),
            { ...filtros, busca, ...novos, pagina: proximaPagina },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };
    const digitar = (texto: string) => {
        setBusca(texto);
        if (timer.current) clearTimeout(timer.current);
        timer.current = setTimeout(() => filtrar({ busca: texto }), 350);
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Laudos', href: route('lab.laudos.index') }]}>
            <Head title="Laudos" />
            <div className="min-w-0 space-y-6 p-4 md:p-8">
                <PageHeader eyebrow="Rotina" title="Laudos" description="Laudos liberados, versões e acompanhamento da entrega." />
                <div className="grid items-end gap-4 md:grid-cols-2 xl:grid-cols-[minmax(0,2fr)_1fr_1fr_minmax(0,1.5fr)]">
                    <FormField id="busca" label="Buscar laudo">
                        <TextInput
                            className="h-11"
                            icon={Search}
                            value={busca}
                            onChange={(e) => digitar(e.target.value)}
                            placeholder="Protocolo, laudo, paciente ou solicitante"
                        />
                    </FormField>
                    <FormField id="de" label="Liberado a partir de">
                        <TextInput className="h-11" type="date" value={filtros.de} onChange={(e) => filtrar({ de: e.target.value })} />
                    </FormField>
                    <FormField id="ate" label="Liberado até">
                        <TextInput className="h-11" type="date" value={filtros.ate} onChange={(e) => filtrar({ ate: e.target.value })} />
                    </FormField>
                    <FormField id="entrega" label="Entrega">
                        <Select className="h-11" value={filtros.entrega} options={entregas} onChange={(entrega) => filtrar({ entrega })} />
                    </FormField>
                </div>
                <DataTable
                    caption="Laudos liberados"
                    rows={laudos}
                    getRowId={(l) => l.id}
                    columns={[
                        {
                            key: 'numero',
                            header: 'Laudo',
                            cell: (l) => (
                                <div className="space-y-1">
                                    <strong className="font-mono">{l.numero}</strong>
                                    <div className="flex flex-wrap gap-1">
                                        {l.retificado && <StatusBadge tone="info">Retificado</StatusBadge>}
                                        {l.emRetificacao && <StatusBadge tone="warn">Em retificação</StatusBadge>}
                                    </div>
                                </div>
                            ),
                        },
                        { key: 'protocolo', header: 'Protocolo', mono: true, cell: (l) => l.protocolo },
                        { key: 'paciente', header: 'Paciente', cell: (l) => l.paciente },
                        { key: 'solicitante', header: 'Solicitante', cell: (l) => l.solicitante },
                        { key: 'liberacao', header: 'Liberado em', mono: true, cell: (l) => l.liberadoEm },
                        { key: 'rt', header: 'RT', cell: (l) => l.rt },
                        {
                            key: 'entrega',
                            header: 'Entrega e acessos',
                            cell: (l) => (
                                <div className="space-y-1">
                                    <div className="flex flex-wrap gap-1">
                                        {l.emailOk && <StatusBadge tone="ok">E-mail entregue</StatusBadge>}
                                        {l.emailFalhou && <StatusBadge tone="crit">E-mail falhou</StatusBadge>}
                                        {!l.emailOk && !l.emailFalhou && <StatusBadge tone="neutral">E-mail pendente</StatusBadge>}
                                    </div>
                                    <p>
                                        Link aberto {l.acessos} {l.acessos === 1 ? 'vez' : 'vezes'}
                                    </p>
                                    {l.ultimoAcesso && <p className="text-muted-foreground text-xs">Último acesso: {l.ultimoAcesso}</p>}
                                </div>
                            ),
                        },
                        {
                            key: 'critico',
                            header: 'Valor crítico',
                            cell: (l) => <StatusBadge tone={l.temValorCritico ? 'crit' : 'neutral'}>{l.temValorCritico ? 'Sim' : 'Não'}</StatusBadge>,
                        },
                        {
                            key: 'acoes',
                            header: 'Ações',
                            cell: (l) => (
                                <div className="flex flex-col items-start gap-1">
                                    <Button variant="ghost" size="sm" asChild>
                                        <a href={route('lab.pedidos.laudo.pdf', { pedido: l.pedidoId })}>Baixar PDF</a>
                                    </Button>
                                    {podeAbrirPedidos && (
                                        <>
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={route('lab.pedidos.show', { pedido: l.pedidoId })}>Abrir pedido</Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    router.post(
                                                        route('lab.pedidos.laudo.reenviar', { pedido: l.pedidoId }),
                                                        {},
                                                        { preserveScroll: true },
                                                    )
                                                }
                                            >
                                                Reenviar e-mail
                                            </Button>
                                        </>
                                    )}
                                </div>
                            ),
                        },
                    ]}
                    emptyState={
                        <EmptyState
                            icon={FileText}
                            title="Nenhum laudo encontrado"
                            description="Ajuste os filtros ou aguarde a liberação de um laudo."
                        />
                    }
                />
                <nav aria-label="Paginação dos laudos" className="flex items-center justify-between gap-4">
                    <Button variant="secondary" disabled={pagina <= 1} onClick={() => filtrar({}, pagina - 1)}>
                        Página anterior
                    </Button>
                    <span className="text-muted-foreground text-sm" aria-live="polite">
                        Página {pagina}
                    </span>
                    <Button variant="secondary" disabled={!temProxima} onClick={() => filtrar({}, pagina + 1)}>
                        Próxima página
                    </Button>
                </nav>
            </div>
        </AppLayout>
    );
}
