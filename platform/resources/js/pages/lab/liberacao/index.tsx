import {
    Alert,
    Button,
    Card,
    ConfirmDialog,
    EmptyState,
    FormField,
    PageHeader,
    StatusBadge,
    TextInput,
    Toast,
    useFlashToast,
} from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FileCheck } from 'lucide-react';
import { useState } from 'react';

type Resultado = {
    id: string;
    itemId: string;
    exame: string;
    nome: string;
    valor: string;
    unidade: string | null;
    classificacao: string;
    referencia: string;
    digitador: string;
    digitadoEm: string;
    anterior: string | null;
    anteriorEm: string | null;
    delta: boolean;
    percentual: number | null;
    avisado: boolean;
    destinatario: string | null;
};
type Pedido = {
    id: string;
    paciente: string;
    protocolo: string;
    solicitante: string;
    telefone: string;
    itens: Record<string, string>;
    resultados: Resultado[];
    pronto: boolean;
};
type Props = {
    fila: { id: string; protocolo: string; paciente: string; solicitante: string; criticos: number; alterados: number }[];
    selecionado: Pedido | null;
    eRt: boolean;
};
const formatar = (valor: string) => (/^-?\d+(?:\.\d+)?$/.test(valor) ? valor.replace('.', ',') : valor);
const critico = (r: Resultado) => r.classificacao.startsWith('critico_');
const alterado = (r: Resultado) => ['alto', 'baixo'].includes(r.classificacao);
const seta = (r: Resultado) => (r.classificacao.endsWith('alto') ? ' ↑' : r.classificacao.endsWith('baixo') ? ' ↓' : '');
function Aviso({ pedido, resultado, eRt }: { pedido: Pedido; resultado: Resultado; eRt: boolean }) {
    const [aberto, setAberto] = useState(false);
    const form = useForm({ resultado_id: resultado.id, destinatario: pedido.solicitante });
    return (
        <section className="border-crit-600 bg-crit-100 rounded-lg border p-4">
            <h3 className="text-crit-600 font-semibold">Valor crítico · {resultado.nome}</h3>
            <p className="mt-2 font-mono">
                {formatar(resultado.valor)} {resultado.unidade}
                {seta(resultado)}
            </p>
            <p className="mt-2 text-sm">
                Avise {pedido.solicitante} pelo telefone <span className="font-mono">{pedido.telefone || 'não cadastrado'}</span>.
            </p>
            {resultado.avisado ? (
                <p className="mt-3 text-sm font-semibold">Aviso registrado para {resultado.destinatario}.</p>
            ) : aberto ? (
                <form
                    className="mt-3 space-y-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post(route('lab.liberacao.aviso', { pedido: pedido.id }), { preserveScroll: true, onSuccess: () => setAberto(false) });
                    }}
                >
                    <FormField id={`aviso-${resultado.id}`} label="Avisei" error={form.errors.destinatario}>
                        <TextInput value={form.data.destinatario} onChange={(e) => form.setData('destinatario', e.target.value)} required />
                    </FormField>
                    {Object.entries(form.errors)
                        .filter(([k]) => k !== 'destinatario')
                        .map(([k, v]) => (
                            <p key={k} role="alert" className="text-crit-600 text-sm">
                                {v}
                            </p>
                        ))}
                    <div className="flex flex-wrap gap-2">
                        <Button type="submit" loading={form.processing}>
                            Confirmar aviso
                        </Button>
                        <Button variant="ghost" onClick={() => setAberto(false)}>
                            Voltar
                        </Button>
                    </div>
                </form>
            ) : (
                <Button
                    className="mt-3 w-full whitespace-normal"
                    variant="secondary"
                    disabled={!eRt || !pedido.telefone}
                    onClick={() => setAberto(true)}
                >
                    Registrar que avisei a clínica
                </Button>
            )}
        </section>
    );
}
function Conferencia({ pedido, eRt }: { pedido: Pedido; eRt: boolean }) {
    const [confirmar, setConfirmar] = useState(false);
    const form = useForm({ observacao: '' });
    const pendente = pedido.resultados.find((r) => critico(r) && !r.avisado);
    const motivo = !eRt
        ? 'Só o responsável técnico libera laudos.'
        : !pedido.pronto
          ? 'Todos os itens precisam de resultados digitados.'
          : pendente
            ? `Registre o aviso do valor crítico de ${pendente.nome} antes de liberar.`
            : null;
    const alertas = pedido.resultados.filter((r) => critico(r) || r.delta);
    return (
        <div className="grid min-w-0 items-start gap-6 2xl:grid-cols-[minmax(0,1fr)_300px]">
            <Card title={`${pedido.protocolo} · ${pedido.paciente}`}>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[620px] text-sm">
                        <caption className="sr-only">Conferência de todos os exames</caption>
                        <thead className="text-muted-foreground border-b text-left text-xs">
                            <tr>
                                {['Exame / Parâmetro', 'Resultado', 'Referência', 'Anterior'].map((t) => (
                                    <th key={t} className="px-3 py-3">
                                        {t}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {pedido.resultados.map((r) => (
                                <tr key={r.id} className={`border-b ${critico(r) ? 'bg-crit-100' : alterado(r) ? 'bg-warn-100' : ''}`}>
                                    <th className="px-3 py-3 text-left font-medium">
                                        <span className="text-muted-foreground block text-xs">{r.exame}</span>
                                        {r.nome}
                                        <span className="text-muted-foreground mt-1 block text-xs font-normal">
                                            Digitado por {r.digitador} em {r.digitadoEm}
                                        </span>
                                    </th>
                                    <td className="px-3 py-3 font-mono">
                                        {r.classificacao === 'qualitativo' ? r.valor : formatar(r.valor)} {r.unidade}
                                        {seta(r)}
                                        <span className="mt-1 block">
                                            <StatusBadge
                                                tone={critico(r) ? 'crit' : alterado(r) ? 'warn' : r.classificacao === 'normal' ? 'ok' : 'neutral'}
                                            >
                                                {critico(r)
                                                    ? 'Crítico'
                                                    : r.classificacao === 'alto'
                                                      ? '↑ Alto'
                                                      : r.classificacao === 'baixo'
                                                        ? '↓ Baixo'
                                                        : r.classificacao === 'normal'
                                                          ? 'Normal'
                                                          : r.classificacao === 'qualitativo'
                                                            ? 'Qualitativo'
                                                            : 'Sem referência'}
                                            </StatusBadge>
                                        </span>
                                    </td>
                                    <td className="px-3 py-3 font-mono text-xs">{r.referencia}</td>
                                    <td className="px-3 py-3 font-mono text-xs">
                                        {r.anterior === null ? '—' : formatar(r.anterior)}
                                        <span className="text-muted-foreground block">{r.anteriorEm}</span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="mt-5 space-y-4">
                    <FormField id="observacao" label="Observação no laudo" optional>
                        <textarea
                            id="observacao"
                            className="border-input bg-card focus-visible:ring-primary min-h-28 w-full rounded-md border p-3 text-sm focus-visible:ring-2"
                            value={form.data.observacao}
                            onChange={(e) => form.setData('observacao', e.target.value)}
                            maxLength={10000}
                        />
                    </FormField>
                    {Object.values(form.errors).map((v, i) => (
                        <p role="alert" key={i} className="text-crit-600 text-sm">
                            {v}
                        </p>
                    ))}
                    {motivo && (
                        <p id="motivo-liberacao" className="text-muted-foreground text-sm">
                            {motivo}
                        </p>
                    )}
                    <div className="flex flex-wrap justify-end gap-3">
                        <Button
                            disabled={!!motivo || form.processing}
                            aria-describedby={motivo ? 'motivo-liberacao' : undefined}
                            onClick={() => setConfirmar(true)}
                        >
                            Liberar laudo
                        </Button>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {Object.entries(pedido.itens)
                            .filter(([, s]) => s === 'resultado_digitado')
                            .map(([id]) => (
                                <Button key={id} variant="ghost" size="sm" onClick={() => router.post(route('lab.bancada.reabrir', { item: id }))}>
                                    Corrigir {pedido.resultados.find((r) => r.itemId === id)?.exame ?? 'exame'}
                                </Button>
                            ))}
                    </div>
                </div>
                <ConfirmDialog
                    open={confirmar}
                    onOpenChange={setConfirmar}
                    title={`Liberar o laudo do ${pedido.paciente}?`}
                    description="Depois de liberado, os resultados ficam fechados para edição. A entrega ao solicitante e ao responsável será disponibilizada em uma próxima etapa."
                    confirmLabel="Liberar laudo"
                    loading={form.processing}
                    onConfirm={() => form.post(route('lab.liberacao.liberar', { pedido: pedido.id }), { onError: () => setConfirmar(false) })}
                />
            </Card>
            <aside className="space-y-4" aria-label="Alertas">
                {alertas.length === 0 && <Alert tone="ok" title="Sem alertas de valor crítico ou mudança." />}
                {alertas.map((r) => (
                    <div key={r.id} className="space-y-4">
                        {critico(r) && <Aviso pedido={pedido} resultado={r} eRt={eRt} />}
                        {r.delta && (
                            <Alert tone="warn" title={`Mudança desde o último exame · ${r.nome}`}>
                                {r.percentual === null ? 'O resultado anterior era zero.' : `Mudou ${r.percentual}% desde ${r.anteriorEm}.`}
                            </Alert>
                        )}
                    </div>
                ))}
            </aside>
        </div>
    );
}
const plural = (n: number, um: string, varios: string) => `${n} ${n === 1 ? um : varios}`;

export default function Liberacao({ fila, selecionado, eRt }: Props) {
    const flash = useFlashToast();
    return (
        <AppLayout breadcrumbs={[{ title: 'Liberação', href: route('lab.liberacao.index') }]}>
            <Head title="Liberação" />
            <div className="min-w-0 space-y-6 p-4 md:p-8">
                <PageHeader
                    title="Liberação"
                    description={`${plural(fila.length, 'laudo aguardando', 'laudos aguardando')} · ${eRt ? 'você é o responsável técnico' : 'só o RT libera'}`}
                />
                <div className="grid min-w-0 items-start gap-6 xl:grid-cols-[240px_minmax(0,1fr)]">
                    <Card title="Aguardando conferência">
                        {fila.length ? (
                            <ul className="divide-y">
                                {fila.map((p) => (
                                    <li key={p.id}>
                                        <Link
                                            className={`hover:bg-accent block rounded px-2 py-4 transition-colors ${selecionado?.id === p.id ? 'bg-secondary' : ''}`}
                                            href={route('lab.liberacao.index', { pedido: p.id })}
                                            aria-current={selecionado?.id === p.id ? 'true' : undefined}
                                        >
                                            <span className="text-muted-foreground font-mono text-xs">{p.protocolo}</span>
                                            <span className="block font-semibold">{p.paciente}</span>
                                            <span className="text-muted-foreground block text-xs">{p.solicitante}</span>
                                            <div className="mt-2 flex flex-wrap gap-1">
                                                {p.criticos > 0 && <StatusBadge tone="crit">{plural(p.criticos, 'crítico', 'críticos')}</StatusBadge>}
                                                {p.alterados > 0 && (
                                                    <StatusBadge tone="warn">{plural(p.alterados, 'alterado', 'alterados')}</StatusBadge>
                                                )}
                                            </div>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState
                                icon={FileCheck}
                                title="Fila em dia"
                                description="Os pedidos com todos os resultados digitados aparecem aqui."
                            />
                        )}
                    </Card>
                    <div className="min-w-0">
                        {selecionado ? (
                            <Conferencia key={selecionado.id} pedido={selecionado} eRt={eRt} />
                        ) : (
                            <EmptyState
                                icon={FileCheck}
                                title="Nenhum laudo aguardando"
                                description="Envie os resultados da bancada para conferência."
                            />
                        )}
                    </div>
                </div>
            </div>
            {flash.message && <Toast message={flash.message} onDismiss={flash.dismiss} className="fixed right-4 bottom-4 z-50" />}
        </AppLayout>
    );
}
