import { Alert, Button, Card, DataTable, DetailList, PageHeader, StatusBadge, StatusStepper, Toast, useFlashToast } from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { Printer } from 'lucide-react';
import { useEffect } from 'react';
import { reais, tomDaSituacao } from './formato';

type Pedido = {
    id: string;
    protocolo: string;
    paciente: { nome: string; descricao: string; especieCurta: string; responsavelNome: string; rotuloDoResponsavel: string };
    solicitante: { nome: string; registro: string };
    pagador: { tipo: string; rotulo: string; descontoPercentual: number };
    prioridade: string;
    situacao: string;
    situacaoRotulo: string;
    criadoEm: string;
    observacao: string | null;
    itens: { id: string; codigo: string; nome: string; material: string; situacao: string; situacaoRotulo: string; precoCentavos: number }[];
    amostras: { id: string; codigo: string; material: string; situacao: string; recebidaEm: string | null }[];
    totalCentavos: number;
    etiquetas: number;
};
type Props = { pedido: Pedido; imprimir: boolean };
type Etapa = 'done' | 'current' | 'todo';

const ETAPAS = ['Pedido', 'Amostra', 'Bancada', 'Liberação', 'Laudo'];
/** Quantas etapas já terminaram, pela situação derivada do pedido. */
const CONCLUIDAS: Record<string, number> = { aguardando_amostra: 1, em_analise: 2, aguardando_liberacao: 3, liberado: 4, cancelado: 0 };

const SITUACAO_AMOSTRA: Record<string, { rotulo: string; tom: 'ok' | 'warn' | 'crit' | 'neutral' }> = {
    aguardando: { rotulo: 'Aguardando chegada', tom: 'neutral' },
    recebida: { rotulo: 'Recebida', tom: 'ok' },
    aceita_com_ressalva: { rotulo: 'Aceita com ressalva', tom: 'warn' },
    rejeitada: { rotulo: 'Rejeitada', tom: 'crit' },
};

export default function Pedido({ pedido, imprimir }: Props) {
    const flash = useFlashToast();
    const etiquetas = route('lab.pedidos.etiquetas', { pedido: pedido.id });

    // Depois de salvar, tenta abrir as etiquetas numa aba nova; se o navegador bloquear, o botão está logo abaixo.
    useEffect(() => {
        if (imprimir && pedido.etiquetas > 0) window.open(etiquetas, '_blank', 'noopener');
    }, [imprimir, pedido.etiquetas, etiquetas]);

    const concluidas = CONCLUIDAS[pedido.situacao] ?? 0;
    const etapas = ETAPAS.map((label, i) => ({
        label,
        status: (pedido.situacao === 'cancelado' ? 'todo' : i < concluidas ? 'done' : i === concluidas ? 'current' : 'todo') as Etapa,
        time: i === 0 ? pedido.criadoEm : undefined,
    }));

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Pedidos', href: route('lab.pedidos.index') },
                { title: pedido.protocolo, href: route('lab.pedidos.show', { pedido: pedido.id }) },
            ]}
        >
            <Head title={`Pedido ${pedido.protocolo}`} />
            <div className="min-w-0 space-y-6 p-4 md:p-8">
                <PageHeader
                    back={{ href: route('lab.pedidos.index'), label: 'Pedidos' }}
                    eyebrow={`Pedido ${pedido.protocolo}`}
                    title={pedido.paciente.nome}
                    description={pedido.paciente.descricao}
                    actions={
                        <>
                            {pedido.prioridade === 'urgente' && <StatusBadge tone="crit">Urgente</StatusBadge>}
                            <StatusBadge tone={tomDaSituacao[pedido.situacao] ?? 'neutral'}>{pedido.situacaoRotulo}</StatusBadge>
                            <Button variant="secondary" icon={Printer} asChild>
                                <a href={etiquetas} target="_blank" rel="noopener noreferrer">
                                    Imprimir etiquetas
                                </a>
                            </Button>
                        </>
                    }
                />
                {imprimir && (
                    <Alert tone="ok" title={`Pedido salvo. ${pedido.etiquetas === 1 ? 'A etiqueta' : 'As etiquetas'} abriram em outra aba.`}>
                        Se nada abriu, use o botão &quot;Imprimir etiquetas&quot; aqui em cima.
                    </Alert>
                )}
                <Card>
                    <StatusStepper label="Andamento do pedido" steps={etapas} />
                </Card>
                <div className="grid items-start gap-6 lg:grid-cols-2">
                    <Card title="Quem pede e quem paga">
                        <DetailList
                            items={[
                                { term: pedido.paciente.rotuloDoResponsavel, value: pedido.paciente.responsavelNome },
                                { term: 'Solicitante', value: `${pedido.solicitante.nome} · ${pedido.solicitante.registro}` },
                                { term: 'Quem paga', value: pedido.pagador.rotulo },
                                { term: 'Prioridade', value: pedido.prioridade === 'urgente' ? 'Urgente' : 'Rotina' },
                                { term: 'Aberto em', value: pedido.criadoEm, mono: true },
                                ...(pedido.observacao ? [{ term: 'Observação', value: pedido.observacao }] : []),
                            ]}
                        />
                    </Card>
                    <Card title="Amostras">
                        <DataTable
                            caption="Amostras do pedido"
                            rows={pedido.amostras}
                            getRowId={(a) => a.id}
                            columns={[
                                {
                                    key: 'codigo',
                                    header: 'Código',
                                    mono: true,
                                    cell: (a) => (
                                        <Link
                                            href={route('lab.amostras.index', { amostra: a.id })}
                                            className="text-brand-500 font-semibold hover:underline"
                                        >
                                            {a.codigo}
                                        </Link>
                                    ),
                                },
                                { key: 'material', header: 'Material', cell: (a) => a.material },
                                {
                                    key: 'situacao',
                                    header: 'Situação',
                                    cell: (a) => (
                                        <StatusBadge tone={SITUACAO_AMOSTRA[a.situacao]?.tom ?? 'neutral'}>
                                            {SITUACAO_AMOSTRA[a.situacao]?.rotulo ?? a.situacao}
                                        </StatusBadge>
                                    ),
                                },
                                { key: 'hora', header: 'Chegou', mono: true, cell: (a) => a.recebidaEm ?? '—' },
                            ]}
                        />
                    </Card>
                </div>
                <Card title="Exames">
                    <DataTable
                        caption="Exames do pedido"
                        rows={pedido.itens}
                        getRowId={(i) => i.id}
                        columns={[
                            { key: 'codigo', header: 'Código', mono: true, cell: (i) => i.codigo },
                            { key: 'nome', header: 'Exame', cell: (i) => i.nome },
                            { key: 'material', header: 'Material', cell: (i) => i.material },
                            {
                                key: 'situacao',
                                header: 'Situação',
                                cell: (i) => <StatusBadge tone={tomDaSituacao[i.situacao] ?? 'neutral'}>{i.situacaoRotulo}</StatusBadge>,
                            },
                            { key: 'preco', header: 'Preço', align: 'right', mono: true, cell: (i) => reais(i.precoCentavos) },
                        ]}
                        footer={
                            <div className="border-border flex items-center justify-between border-t px-4 py-3 text-sm font-semibold">
                                <span>
                                    {pedido.pagador.tipo === 'clinica'
                                        ? `Total na tabela da clínica · desconto de ${pedido.pagador.descontoPercentual}%`
                                        : 'Total'}
                                </span>
                                <span className="font-mono tabular-nums">{reais(pedido.totalCentavos)}</span>
                            </div>
                        }
                    />
                </Card>
            </div>
            {flash.message && <Toast message={flash.message} onDismiss={flash.dismiss} className="fixed right-4 bottom-4 z-50" />}
        </AppLayout>
    );
}
