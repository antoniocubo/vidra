import { Button, DataTable, FormField, MetricCard, PageHeader, StatusBadge, TextInput } from '@/components/vidra';
import VetLayout, { type IdentidadeVet, type LaboratorioVet } from '@/layouts/vet-layout';
import { Head, router, useForm } from '@inertiajs/react';

type Pedido = {
    id: string;
    animal: string;
    tutor: string;
    exames: string[];
    situacao: string;
    situacaoRotulo: string;
    critico: boolean;
    pdfPronto: boolean;
    whatsapp: string | null;
};
type Props = {
    identidade: IdentidadeVet;
    laboratorio: LaboratorioVet;
    laboratorios: LaboratorioVet[];
    busca: string;
    lista: {
        pedidos: Pedido[];
        resumo: { andamento: number; prontosHoje: number; criticosHoje: number; mes: number };
        pagina: number;
        paginas: number;
        total: number;
    };
};
export default function Inicio({ identidade, laboratorio, laboratorios, lista, busca }: Props) {
    const form = useForm({ busca });
    function pagina(numero: number) {
        router.get(route('vet.inicio'), { busca, pagina: numero }, { preserveState: true });
    }
    return (
        <VetLayout identidade={identidade} laboratorio={laboratorio} laboratorios={laboratorios}>
            <Head title="Pedidos e laudos" />
            <PageHeader title="Pedidos e laudos" description="Acompanhe os pedidos e laudos do laboratório escolhido." />
            <section className="grid gap-4 sm:grid-cols-3" aria-label="Resumo">
                <MetricCard label="Em andamento" value={lista.resumo.andamento} />
                <MetricCard
                    label="Laudos prontos hoje"
                    value={lista.resumo.prontosHoje}
                    footnote={`${lista.resumo.criticosHoje} com valor crítico`}
                    tone={lista.resumo.criticosHoje ? 'crit' : 'default'}
                />
                <MetricCard label="Pedidos este mês" value={lista.resumo.mes} />
            </section>
            <DataTable
                caption="Pedidos recentes"
                rows={lista.pedidos}
                getRowId={(p) => p.id}
                rowTone={(p) => (p.critico ? 'critical' : undefined)}
                toolbar={
                    <form
                        className="flex flex-wrap items-end justify-between gap-4 p-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.get(route('vet.inicio'), { preserveState: true });
                        }}
                    >
                        <h2 className="font-display text-xl font-semibold">Recentes</h2>
                        <div className="flex items-end gap-2">
                            <FormField id="busca" label="Buscar animal ou tutor">
                                <TextInput className="h-11" value={form.data.busca} onChange={(e) => form.setData('busca', e.target.value)} />
                            </FormField>
                            <Button type="submit" loading={form.processing}>
                                Buscar
                            </Button>
                        </div>
                    </form>
                }
                columns={[
                    { key: 'animal', header: 'Animal', cell: (p) => <strong className="font-semibold">{p.animal}</strong> },
                    { key: 'tutor', header: 'Tutor', cell: (p) => p.tutor },
                    { key: 'exames', header: 'Exames', cell: (p) => p.exames.join(', ') },
                    {
                        key: 'situacao',
                        header: 'Situação',
                        cell: (p) => (
                            <StatusBadge tone={p.critico ? 'crit' : p.situacao === 'liberado' ? 'ok' : 'info'}>
                                {p.critico ? 'Pronto · valor crítico' : p.situacaoRotulo}
                            </StatusBadge>
                        ),
                    },
                    {
                        key: 'acoes',
                        header: 'Laudo',
                        align: 'right',
                        cell: (p) =>
                            p.situacao === 'liberado' ? (
                                <div className="flex flex-wrap justify-end gap-3">
                                    {p.pdfPronto ? (
                                        <a
                                            className="text-primary min-h-11 content-center font-semibold"
                                            href={route('vet.laudo.pdf', { pedido: p.id })}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            Abrir PDF
                                        </a>
                                    ) : (
                                        <span className="text-muted-foreground">PDF em preparação</span>
                                    )}
                                    {p.whatsapp && (
                                        <a
                                            className="text-primary min-h-11 content-center font-semibold"
                                            href={p.whatsapp}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            Compartilhar pelo WhatsApp
                                        </a>
                                    )}
                                </div>
                            ) : (
                                '—'
                            ),
                    },
                ]}
                emptyState={<p className="text-muted-foreground py-6 text-center">Nenhum pedido encontrado.</p>}
                footer={
                    <div className="flex flex-wrap items-center justify-between gap-3 p-4">
                        <span className="text-muted-foreground text-sm">
                            {lista.total} pedidos · página {lista.pagina} de {lista.paginas}
                        </span>
                        <div className="flex gap-2">
                            <Button variant="secondary" disabled={lista.pagina <= 1} onClick={() => pagina(lista.pagina - 1)}>
                                Anterior
                            </Button>
                            <Button variant="secondary" disabled={lista.pagina >= lista.paginas} onClick={() => pagina(lista.pagina + 1)}>
                                Próxima
                            </Button>
                        </div>
                    </div>
                }
            />
            <p className="text-muted-foreground text-xs">
                Para mandar pelo seu WhatsApp, use Compartilhar: o texto e o link do tutor já vão escritos. Valores críticos são comunicados pelo
                laboratório.
            </p>
        </VetLayout>
    );
}
