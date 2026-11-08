import { Button, Card, EmptyState, MetricCard, PageHeader, StatusBadge, StatusStepper } from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { CheckCheck, ClipboardList, Plus } from 'lucide-react';

type Situacao = 'aguardando_amostra' | 'em_analise' | 'aguardando_liberacao' | 'liberado' | 'cancelado';
type Atencao = {
    id: string;
    pedidoId: string;
    protocolo: number;
    tipo: 'critico' | 'recoleta' | 'urgente' | 'atrasado';
    detalhe: string;
    destino: string;
};
type Props = {
    primeiroNome: string;
    painel: {
        data: string;
        atualizadoEm: string;
        saudacao: string;
        indicadores: { pedidosHoje: number; amostrasAguardando: number; naBancada: number; aguardandoLiberacao: number; laudosHoje: number };
        atencao: Atencao[];
        andamento: { id: string; protocolo: number; situacao: Situacao }[];
    };
};

const rotulos: Record<Situacao, string> = {
    aguardando_amostra: 'Aguardando amostra',
    em_analise: 'Em análise',
    aguardando_liberacao: 'Aguardando liberação',
    liberado: 'Liberado',
    cancelado: 'Cancelado',
};
const etapas = ['Pedido', 'Amostra', 'Bancada', 'Liberação', 'Laudo'];
const etapaAtual: Record<Situacao, number> = { aguardando_amostra: 1, em_analise: 2, aguardando_liberacao: 3, liberado: 5, cancelado: 0 };
const alertas = { critico: 'Crítico sem aviso', recoleta: 'Recoleta pendente', urgente: 'Urgente', atrasado: 'Atrasado' };
const protocolo = (numero: number) => `P-${String(numero).padStart(5, '0')}`;
const foco = 'rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring';

function destino(linha: Atencao) {
    if (linha.tipo === 'recoleta') return route('lab.amostras.index', { amostra: linha.destino });
    if (linha.tipo === 'critico') return route('lab.liberacao.index', { pedido: linha.pedidoId });
    return route('lab.pedidos.show', { pedido: linha.pedidoId });
}

export default function Dashboard({ painel, primeiroNome }: Props) {
    const { indicadores: i } = painel;
    const indicadores = [
        { label: 'Pedidos hoje', value: i.pedidosHoje, href: route('lab.pedidos.index', { data: painel.data }) },
        { label: 'Amostras aguardando chegada', value: i.amostrasAguardando, href: `${route('lab.amostras.index')}#aguardando` },
        { label: 'Na bancada', value: i.naBancada, href: route('lab.bancada.index'), footnote: 'Itens em análise' },
        { label: 'Aguardando liberação', value: i.aguardandoLiberacao, href: route('lab.liberacao.index'), footnote: 'Pedidos na fila do RT' },
        { label: 'Laudos liberados hoje', value: i.laudosHoje, href: route('lab.pedidos.index', { data: painel.data, situacao: 'liberado' }) },
    ];

    return (
        <AppLayout breadcrumbs={[{ title: 'Painel', href: '/dashboard' }]}>
            <Head title="Painel do laboratório" />
            <div className="flex flex-col gap-6 p-4 md:p-8">
                <PageHeader
                    eyebrow={painel.atualizadoEm}
                    title={`${painel.saudacao}, ${primeiroNome}`}
                    description="O que está acontecendo no laboratório agora."
                    actions={
                        <Button asChild>
                            <Link href={route('lab.pedidos.create')}>
                                <Plus aria-hidden="true" className="size-4" />
                                Novo pedido
                            </Link>
                        </Button>
                    }
                />
                <section aria-label="Indicadores do laboratório" className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    {indicadores.map(({ href, ...indicador }) => (
                        <Link key={indicador.label} href={href} className={foco}>
                            <MetricCard {...indicador} className="h-full" />
                        </Link>
                    ))}
                </section>
                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                    <Card
                        title="Andamento de hoje"
                        action={
                            <Link href={route('lab.pedidos.index', { data: painel.data })} className={`text-brand-500 text-sm font-semibold ${foco}`}>
                                Ver todos os pedidos
                            </Link>
                        }
                    >
                        {painel.andamento.length === 0 ? (
                            <EmptyState icon={ClipboardList} title="Nenhum pedido hoje" description="Os pedidos abertos hoje aparecem aqui." />
                        ) : (
                            <ul className="divide-border divide-y">
                                {painel.andamento.map((pedido) => (
                                    <li key={pedido.id} className="flex flex-col gap-4 py-4">
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <Link
                                                className={`text-brand-500 font-mono font-semibold ${foco}`}
                                                href={route('lab.pedidos.show', { pedido: pedido.id })}
                                            >
                                                {protocolo(pedido.protocolo)}
                                            </Link>
                                            <StatusBadge
                                                tone={
                                                    pedido.situacao === 'liberado'
                                                        ? 'ok'
                                                        : pedido.situacao === 'aguardando_liberacao'
                                                          ? 'warn'
                                                          : 'neutral'
                                                }
                                            >
                                                {rotulos[pedido.situacao]}
                                            </StatusBadge>
                                        </div>
                                        {pedido.situacao !== 'cancelado' && (
                                            <StatusStepper
                                                label={`Andamento ${protocolo(pedido.protocolo)}`}
                                                steps={etapas.map((label, index) => ({
                                                    label,
                                                    status:
                                                        index < etapaAtual[pedido.situacao]
                                                            ? 'done'
                                                            : index === etapaAtual[pedido.situacao]
                                                              ? 'current'
                                                              : 'todo',
                                                }))}
                                                className="[&_li_span.text-sm]:text-xs [&>li]:min-w-20 [&>li]:gap-1"
                                            />
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                    <Card title="Atenção agora" className="row-start-1 xl:col-start-2">
                        {painel.atencao.length === 0 ? (
                            <EmptyState
                                icon={CheckCheck}
                                title="Nada pedindo atenção agora"
                                description="Não há pendências críticas, recoletas, urgências ou atrasos."
                            />
                        ) : (
                            <ul className="divide-border divide-y">
                                {painel.atencao.map((linha) => (
                                    <li key={linha.id}>
                                        <Link href={destino(linha)} className={`hover:bg-secondary flex flex-col items-start gap-2 py-3 ${foco}`}>
                                            <StatusBadge tone={linha.tipo === 'critico' ? 'crit' : 'warn'}>{alertas[linha.tipo]}</StatusBadge>
                                            <span className="font-mono text-sm font-semibold">{protocolo(linha.protocolo)}</span>
                                            <span className="text-muted-foreground text-sm">{linha.detalhe}</span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
