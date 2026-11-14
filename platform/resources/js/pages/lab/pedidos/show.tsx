import {
    Alert,
    Button,
    Card,
    ConfirmDialog,
    DataTable,
    DetailList,
    FormField,
    PageHeader,
    StatusBadge,
    StatusStepper,
    TabPanel,
    Tabs,
    TextInput,
    useFlashToast,
} from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Printer } from 'lucide-react';
import { useEffect, useState } from 'react';
import { BuscaDeExame, type Exame } from './busca-de-exame';
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
    itens: {
        exameId: string;
        cancelamento: { motivo: string; por: string; em: string } | null;
        id: string;
        codigo: string;
        nome: string;
        material: string;
        situacao: string;
        situacaoRotulo: string;
        precoCentavos: number;
    }[];
    amostras: { id: string; codigo: string; material: string; situacao: string; recebidaEm: string | null }[];
    totalCentavos: number;
    etiquetas: number;
    podeCancelar: boolean;
    podeIncluirExame: boolean;
};
type Props = {
    pedido: Pedido;
    historico: { id: string; em: string; quem: string; detalhe: string }[];
    exames: Exame[];
    novaAmostra: boolean;
    imprimir: boolean;
    entrega: { pdfPronto: boolean; pdfErro: string | null; whatsapp: string | null; link: string; entregas: string[] } | null;
    laudo: {
        numero: string;
        rt: string;
        registro: string;
        usuario: string;
        quando: string;
        codigo: string;
        reabertoMotivo: string | null;
        versoes: { versao: number; em: string; por: string; motivo: string | null; pdf: string | null }[];
    } | null;
};
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

export default function Pedido({ pedido, imprimir, laudo, entrega, exames, novaAmostra, historico }: Props) {
    const [aba, setAba] = useState<'detalhes' | 'historico'>('detalhes');
    const { laboratorio } = usePage<SharedData>().props;
    const [retificar, setRetificar] = useState(false);
    const retificacao = useForm({ motivo: '' });
    const [cancelar, setCancelar] = useState<{ item?: string; nome: string } | null>(null);
    const cancelamento = useForm({ motivo: '' });
    const inclusao = useForm({ exame_id: '' });
    const errosCancelamento: Record<string, string | undefined> = cancelamento.errors;
    const errosInclusao: Record<string, string | undefined> = inclusao.errors;
    const abrirCancelamento = (alvo: { item?: string; nome: string }) => {
        cancelamento.reset();
        cancelamento.clearErrors();
        setCancelar(alvo);
    };
    const confirmarCancelamento = () => {
        if (!cancelar) return;
        if (!cancelamento.data.motivo.trim()) {
            cancelamento.setError('motivo', 'Escreva o motivo do cancelamento.');
            return;
        }
        const url = cancelar.item
            ? route('lab.pedidos.itens.cancelar', { pedido: pedido.id, item: cancelar.item })
            : route('lab.pedidos.cancelar', { pedido: pedido.id });
        cancelamento.post(url, { preserveScroll: true, onSuccess: () => setCancelar(null) });
    };
    const etiquetas = route('lab.pedidos.etiquetas', { pedido: pedido.id });
    useFlashToast(novaAmostra ? { label: 'Imprimir etiquetas', onClick: () => window.open(etiquetas, '_blank', 'noopener') } : undefined);

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
                            {pedido.podeCancelar && (
                                <Button variant="danger" onClick={() => abrirCancelamento({ nome: 'pedido' })}>
                                    Cancelar pedido
                                </Button>
                            )}
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
                    <Alert tone="ok" title={`Pedido salvo. ${pedido.etiquetas === 1 ? 'A etiqueta abriu' : 'As etiquetas abriram'} em outra aba.`}>
                        Se nada abriu, use o botão &quot;Imprimir etiquetas&quot; aqui em cima.
                    </Alert>
                )}
                <Tabs
                    id="pedido"
                    label="Informações do pedido"
                    value={aba}
                    onChange={setAba}
                    tabs={[
                        { value: 'detalhes', label: 'Detalhes' },
                        { value: 'historico', label: 'Histórico' },
                    ]}
                />
                {aba === 'historico' && (
                    <TabPanel tabsId="pedido" value="historico">
                        <Card title="Histórico do pedido">
                            {historico.length === 0 ? (
                                <p className="text-muted-foreground">Nenhum evento registrado.</p>
                            ) : (
                                <ol className="divide-border divide-y">
                                    {historico.map((evento) => (
                                        <li key={evento.id} className="space-y-1 py-4">
                                            <p className="text-muted-foreground text-sm">
                                                <time dateTime={evento.em}>
                                                    {new Intl.DateTimeFormat('pt-BR', {
                                                        dateStyle: 'short',
                                                        timeStyle: 'short',
                                                        timeZone: 'America/Sao_Paulo',
                                                    })
                                                        .format(new Date(evento.em))
                                                        .replace(', ', ' ')}
                                                </time>{' '}
                                                · {evento.quem}
                                            </p>
                                            <p>{evento.detalhe}</p>
                                        </li>
                                    ))}
                                </ol>
                            )}
                        </Card>
                    </TabPanel>
                )}
                {aba === 'detalhes' && (
                    <TabPanel tabsId="pedido" value="detalhes" className="space-y-6">
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
                        {laudo && (
                            <Card title={`Laudo ${laudo.numero}`}>
                                {laudo.reabertoMotivo ? (
                                    <Alert tone="warn" title={`Laudo em retificação · ${laudo.reabertoMotivo}`}>
                                        <Link className="underline" href={route('lab.liberacao.index', { pedido: pedido.id })}>
                                            Ir para a liberação
                                        </Link>
                                    </Alert>
                                ) : (
                                    laboratorio?.eRt && (
                                        <Button variant="secondary" onClick={() => setRetificar(true)}>
                                            Retificar laudo
                                        </Button>
                                    )
                                )}
                                <ConfirmDialog
                                    open={retificar}
                                    onOpenChange={setRetificar}
                                    title="Retificar laudo?"
                                    description="A versão atual continua valendo até você liberar a nova. Quem já recebeu o laudo recebe a nova versão."
                                    confirmLabel="Retificar laudo"
                                    loading={retificacao.processing}
                                    onConfirm={() =>
                                        retificacao.post(route('lab.pedidos.laudo.retificar', { pedido: pedido.id }), {
                                            preserveScroll: true,
                                            onSuccess: () => setRetificar(false),
                                        })
                                    }
                                >
                                    <FormField id="motivo-retificacao" label="Motivo da retificação" error={retificacao.errors.motivo}>
                                        <TextInput
                                            id="motivo-retificacao"
                                            className="h-11"
                                            required
                                            maxLength={500}
                                            value={retificacao.data.motivo}
                                            onChange={(e) => retificacao.setData('motivo', e.target.value)}
                                        />
                                    </FormField>
                                    {Object.entries(retificacao.errors)
                                        .filter(([k]) => k !== 'motivo')
                                        .map(([k, v]) => (
                                            <p key={k} role="alert" className="text-crit-600">
                                                {v}
                                            </p>
                                        ))}
                                </ConfirmDialog>
                                <div className="my-4 space-y-2">
                                    <h3 className="font-semibold">Histórico de versões</h3>
                                    {laudo.versoes.map((v) => (
                                        <p key={v.versao} className="text-sm">
                                            Versão {v.versao} · {v.em} · {v.por}
                                            {v.motivo && ` · ${v.motivo}`}{' '}
                                            {v.pdf && (
                                                <a className="text-primary underline" href={v.pdf}>
                                                    Baixar PDF da versão {v.versao}
                                                </a>
                                            )}
                                        </p>
                                    ))}
                                </div>
                                <DetailList
                                    items={[
                                        { term: 'Liberado por', value: `${laudo.usuario} · RT ${laudo.rt} · ${laudo.registro}` },
                                        { term: 'Liberado em', value: laudo.quando, mono: true },
                                        { term: 'Código de acesso', value: laudo.codigo, mono: true },
                                    ]}
                                />
                                {entrega && (
                                    <div className="mt-5 space-y-4">
                                        {entrega.pdfErro ? (
                                            <Alert tone="warn" title="Não foi possível gerar o PDF">
                                                A entrega será tentada novamente. Se o problema continuar, consulte o suporte.
                                            </Alert>
                                        ) : (
                                            !entrega.pdfPronto && (
                                                <p role="status" className="text-muted-foreground text-sm">
                                                    Gerando o PDF… Recarregue para consultar o andamento.
                                                </p>
                                            )
                                        )}
                                        <div className="flex flex-wrap gap-3">
                                            {entrega.pdfPronto && (
                                                <Button asChild>
                                                    <a href={route('lab.pedidos.laudo.pdf', { pedido: pedido.id })}>Baixar o laudo em PDF</a>
                                                </Button>
                                            )}
                                            {entrega.whatsapp && (
                                                <Button variant="secondary" asChild>
                                                    <a href={entrega.whatsapp} target="_blank" rel="noopener noreferrer">
                                                        Compartilhar no WhatsApp
                                                    </a>
                                                </Button>
                                            )}
                                            {/* Sem PDF pronto, o mesmo botão refaz a entrega completa: o laudo nunca fica preso. */}
                                            <Button
                                                variant="secondary"
                                                onClick={() =>
                                                    router.post(
                                                        route('lab.pedidos.laudo.reenviar', { pedido: pedido.id }),
                                                        {},
                                                        { preserveScroll: true },
                                                    )
                                                }
                                            >
                                                {entrega.pdfPronto ? 'Reenviar e-mail' : 'Gerar PDF e enviar de novo'}
                                            </Button>
                                            {!entrega.pdfPronto && (
                                                <Button variant="ghost" onClick={() => router.reload({ only: ['entrega'] })}>
                                                    Atualizar andamento
                                                </Button>
                                            )}
                                        </div>
                                        <a href={entrega.link} className="text-brand-500 text-sm underline" target="_blank" rel="noopener noreferrer">
                                            Ver link de acesso
                                        </a>
                                        {entrega.entregas.length > 0 && (
                                            <ul className="text-muted-foreground space-y-2 text-sm">
                                                {entrega.entregas.map((texto, i) => (
                                                    <li key={i} className="break-words">
                                                        {texto}
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </div>
                                )}
                            </Card>
                        )}
                        <Card title="Exames">
                            {pedido.podeIncluirExame && (
                                <div className="mb-4">
                                    <BuscaDeExame
                                        exames={exames}
                                        desconto={pedido.pagador.tipo === 'clinica' ? pedido.pagador.descontoPercentual : 0}
                                        escolhidos={pedido.itens.filter((i) => i.situacao !== 'cancelado').map((i) => i.exameId)}
                                        disabled={inclusao.processing}
                                        error={errosInclusao.exame_id ?? errosInclusao.exame_ids ?? errosInclusao.form}
                                        onEscolher={(exame) => {
                                            inclusao.transform(() => ({ exame_id: exame.id }));
                                            inclusao.post(route('lab.pedidos.itens.store', { pedido: pedido.id }), { preserveScroll: true });
                                        }}
                                    />
                                </div>
                            )}
                            <DataTable
                                caption="Exames do pedido"
                                rows={pedido.itens}
                                getRowId={(i) => i.id}
                                columns={[
                                    { key: 'codigo', header: 'Código', mono: true, cell: (i) => i.codigo },
                                    {
                                        key: 'nome',
                                        header: 'Exame',
                                        cell: (i) => (
                                            <div>
                                                <span className={i.situacao === 'cancelado' ? 'text-muted-foreground line-through' : ''}>
                                                    {i.nome}
                                                </span>
                                                {i.cancelamento && (
                                                    <p className="text-muted-foreground mt-1 text-xs">
                                                        Motivo: {i.cancelamento.motivo} · por {i.cancelamento.por} em {i.cancelamento.em}
                                                    </p>
                                                )}
                                            </div>
                                        ),
                                    },
                                    { key: 'material', header: 'Material', cell: (i) => i.material },
                                    {
                                        key: 'situacao',
                                        header: 'Situação',
                                        cell: (i) => (
                                            <StatusBadge tone={i.situacao === 'cancelado' ? 'neutral' : (tomDaSituacao[i.situacao] ?? 'neutral')}>
                                                {i.situacaoRotulo}
                                            </StatusBadge>
                                        ),
                                    },
                                    {
                                        key: 'acoes',
                                        header: 'Ações',
                                        cell: (i) =>
                                            pedido.podeCancelar && i.situacao !== 'cancelado' && i.situacao !== 'liberado' ? (
                                                <Button variant="ghost" onClick={() => abrirCancelamento({ item: i.id, nome: i.nome })}>
                                                    Cancelar exame
                                                </Button>
                                            ) : null,
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
                    </TabPanel>
                )}
            </div>
            <ConfirmDialog
                open={cancelar !== null}
                onOpenChange={(open) => {
                    if (!open && !cancelamento.processing) setCancelar(null);
                }}
                title={`Cancelar ${cancelar?.nome ?? 'pedido'}?`}
                description="O cancelamento fica registrado no pedido com o motivo e quem cancelou."
                confirmLabel={cancelar?.item ? 'Cancelar exame' : 'Cancelar pedido'}
                tone="danger"
                loading={cancelamento.processing}
                onConfirm={confirmarCancelamento}
            >
                <FormField id="motivo-cancelamento" label="Motivo do cancelamento" error={errosCancelamento.motivo}>
                    <TextInput
                        id="motivo-cancelamento"
                        className="h-11"
                        required
                        maxLength={500}
                        value={cancelamento.data.motivo}
                        onChange={(event) => cancelamento.setData('motivo', event.target.value)}
                    />
                </FormField>
                {errosCancelamento.form && <Alert tone="crit" title={errosCancelamento.form} />}
            </ConfirmDialog>
        </AppLayout>
    );
}
