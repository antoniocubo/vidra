import {
    Alert,
    Button,
    ConfirmDialog,
    DataTable,
    DetailList,
    EmptyState,
    FormField,
    MetricCard,
    PageHeader,
    Select,
    StatusBadge,
    TextInput,
    Toast,
    useFlashToast,
    type Tone,
} from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Receipt } from 'lucide-react';
import { useState } from 'react';

type Pagador = {
    id: string | null;
    numero: string | null;
    pagador: string;
    tipo: 'clinica' | 'solicitante';
    pedidos: number;
    valor: number;
    situacao: string;
    situacaoRotulo: string;
    vencimento: string | null;
};
type Fatura = {
    id: string;
    numero: string;
    competencia: string;
    pagador: string;
    email: string | null;
    vencimento: string;
    situacao: string;
    situacaoRotulo: string;
    enviadaEm: string | null;
    pagaEm: string | null;
    subtotal: number;
    totalCreditos: number;
    total: number;
    emCobranca: boolean;
    linhas: { protocolo: string; paciente: string; liberadoEm: string; exames: string; valor: number }[];
    creditos: { motivo: string; valor: number; em: string }[];
};
type Resumo = {
    clinicas: number;
    clinicasPedidos: number;
    solicitantes: number;
    solicitantesPedidos: number;
    recebido: number;
    emAtraso: number;
    emAtrasoQuem: string;
};
type Props = {
    competencia: string;
    competencias: { value: string; label: string }[];
    resumo: Resumo;
    pagadores: Pagador[];
    fatura: Fatura | null;
};

const reais = (centavos: number) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(centavos / 100);
const dataLocal = (iso: string) => new Intl.DateTimeFormat('pt-BR', { timeZone: 'America/Sao_Paulo' }).format(new Date(iso));
// Datas sem hora (vencimento) não passam por fuso: só viram dd/mm/aaaa.
const dataSimples = (dia: string) => dia.split('-').reverse().join('/');
const tons: Record<string, Tone> = { a_fechar: 'neutral', aberta: 'neutral', enviada: 'info', paga: 'ok', vencida: 'crit' };
const pedidos = (n: number) => `${n} ${n === 1 ? 'pedido' : 'pedidos'}`;

export default function Financeiro({ competencia, competencias, resumo, pagadores, fatura }: Props) {
    const { errors } = usePage<SharedData>().props;
    const flash = useFlashToast();
    const [fechando, setFechando] = useState(false);
    const [recebendo, setRecebendo] = useState(false);
    const [creditando, setCreditando] = useState(false);
    const [ocupado, setOcupado] = useState(false);
    const credito = useForm({ motivo: '', valor: '' });
    const rotulo = competencias.find((c) => c.value === competencia)?.label ?? competencia;

    const abrir = (params: { competencia?: string; fatura?: string }) =>
        router.get(route('lab.financeiro.index'), { competencia, ...params }, { preserveScroll: true, preserveState: true, replace: true });
    const enviar = (url: string, onSuccess?: () => void) => {
        setOcupado(true);
        router.post(url, {}, { preserveScroll: true, onSuccess, onFinish: () => setOcupado(false) });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Financeiro', href: route('lab.financeiro.index') }]}>
            <Head title="Financeiro" />
            <div className="min-w-0 space-y-6 p-4 md:p-8">
                <PageHeader
                    eyebrow="Financeiro"
                    title={`Faturas de ${rotulo}`}
                    description="Clínicas e veterinários que pagam por fatura mensal. O tutor paga no balcão e não entra aqui."
                    actions={
                        <Button disabled={ocupado} onClick={() => setFechando(true)}>
                            Fechar agora
                        </Button>
                    }
                />
                {errors.form && <Alert tone="crit" title={errors.form} />}

                <div className="grid items-end gap-4 sm:grid-cols-[220px_minmax(0,1fr)]">
                    <FormField id="competencia" label="Competência">
                        <Select value={competencia} options={competencias} onChange={(value) => abrir({ competencia: value, fatura: undefined })} />
                    </FormField>
                </div>

                <section aria-label="Resumo do mês" className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <MetricCard label="A faturar (clínicas)" value={reais(resumo.clinicas)} footnote={pedidos(resumo.clinicasPedidos)} />
                    <MetricCard label="A faturar (veterinários)" value={reais(resumo.solicitantes)} footnote={pedidos(resumo.solicitantesPedidos)} />
                    <MetricCard label="Recebido" value={reais(resumo.recebido)} footnote="Faturas pagas desta competência" />
                    <MetricCard
                        label="Em atraso de meses anteriores"
                        value={reais(resumo.emAtraso)}
                        footnote={resumo.emAtrasoQuem || 'Nenhuma fatura vencida'}
                        tone={resumo.emAtraso > 0 ? 'crit' : 'default'}
                    />
                </section>

                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_400px]">
                    <DataTable<Pagador>
                        caption={`Faturas de ${rotulo} por pagador`}
                        rows={pagadores}
                        getRowId={(p) => p.id ?? `${p.tipo}-${p.pagador}`}
                        selectedId={fatura?.id}
                        emptyState={
                            <EmptyState
                                icon={Receipt}
                                title="Nada a faturar nesta competência"
                                description="Pedidos com laudo liberado, pagos por clínica ou veterinário, aparecem aqui."
                            />
                        }
                        columns={[
                            {
                                key: 'pagador',
                                header: 'Pagador',
                                cell: (p) => (
                                    <div className="min-w-0">
                                        {p.id ? (
                                            <Link
                                                href={route('lab.financeiro.index', { competencia, fatura: p.id })}
                                                preserveScroll
                                                className="text-brand-500 font-semibold underline-offset-2 hover:underline"
                                            >
                                                {p.pagador}
                                            </Link>
                                        ) : (
                                            <span className="font-semibold">{p.pagador}</span>
                                        )}
                                        <p className="text-muted-foreground text-xs">
                                            {p.tipo === 'clinica' ? 'Clínica' : 'Veterinário'}
                                            {p.numero && ` · ${p.numero}`}
                                        </p>
                                    </div>
                                ),
                            },
                            { key: 'pedidos', header: 'Pedidos', align: 'right', mono: true, cell: (p) => p.pedidos },
                            { key: 'valor', header: 'Valor', align: 'right', mono: true, cell: (p) => reais(p.valor) },
                            {
                                key: 'vencimento',
                                header: 'Vence em',
                                mono: true,
                                cell: (p) => (p.vencimento ? dataSimples(p.vencimento) : <span className="text-muted-foreground">—</span>),
                            },
                            {
                                key: 'situacao',
                                header: 'Situação',
                                cell: (p) => <StatusBadge tone={tons[p.situacao] ?? 'neutral'}>{p.situacaoRotulo}</StatusBadge>,
                            },
                        ]}
                    />

                    {fatura && (
                        <aside aria-label={`Fatura ${fatura.numero}`} className="border-border bg-card space-y-4 rounded-lg border p-4">
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <div className="min-w-0">
                                    <h2 className="font-display text-xl font-semibold">{fatura.pagador}</h2>
                                    <p className="text-muted-foreground font-mono text-xs">
                                        {fatura.numero} · competência {fatura.competencia}
                                    </p>
                                </div>
                                <StatusBadge tone={tons[fatura.situacao] ?? 'neutral'}>{fatura.situacaoRotulo}</StatusBadge>
                            </div>
                            <DetailList
                                items={[
                                    { term: 'E-mail', value: fatura.email ?? 'Sem e-mail de faturamento' },
                                    { term: 'Vencimento', value: dataSimples(fatura.vencimento), mono: true },
                                    { term: 'Enviada em', value: fatura.enviadaEm ? dataLocal(fatura.enviadaEm) : 'Ainda não enviada', mono: true },
                                    ...(fatura.pagaEm ? [{ term: 'Paga em', value: dataLocal(fatura.pagaEm), mono: true }] : []),
                                ]}
                            />
                            {!fatura.email && <Alert tone="warn" title="Cadastre o e-mail de faturamento da clínica para enviar a fatura." />}

                            <div className="overflow-x-auto">
                                <table className="w-full border-collapse text-left text-sm">
                                    <caption className="sr-only">Pedidos da fatura {fatura.numero}</caption>
                                    <thead>
                                        <tr className="text-muted-foreground text-xs">
                                            <th scope="col" className="py-1.5 pr-2 font-semibold">
                                                Pedido
                                            </th>
                                            <th scope="col" className="py-1.5 pr-2 font-semibold">
                                                Exames
                                            </th>
                                            <th scope="col" className="py-1.5 text-right font-semibold">
                                                Valor
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {fatura.linhas.map((l) => (
                                            <tr key={l.protocolo} className="border-border border-t align-top">
                                                <td className="py-2 pr-2">
                                                    <span className="font-mono text-xs">{l.protocolo}</span>
                                                    <p>{l.paciente}</p>
                                                    <p className="text-muted-foreground text-xs">{dataLocal(l.liberadoEm)}</p>
                                                </td>
                                                <td className="text-muted-foreground py-2 pr-2 text-xs">{l.exames}</td>
                                                <td className="py-2 text-right font-mono tabular-nums">{reais(l.valor)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <dl className="border-border space-y-1 border-t pt-3 text-sm">
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Subtotal</dt>
                                    <dd className="font-mono tabular-nums">{reais(fatura.subtotal)}</dd>
                                </div>
                                {fatura.creditos.map((c) => (
                                    <div key={`${c.em}-${c.motivo}`} className="flex justify-between gap-3">
                                        <dt className="text-muted-foreground min-w-0">
                                            Crédito: {c.motivo} <span className="text-xs">({dataLocal(c.em)})</span>
                                        </dt>
                                        <dd className="font-mono whitespace-nowrap tabular-nums">− {reais(c.valor)}</dd>
                                    </div>
                                ))}
                                <div className="flex justify-between text-base font-semibold">
                                    <dt>Total</dt>
                                    <dd className="font-mono tabular-nums">{reais(fatura.total)}</dd>
                                </div>
                            </dl>

                            <div className="flex flex-wrap gap-2">
                                <Button asChild variant="secondary">
                                    <a href={route('lab.financeiro.pdf', { fatura: fatura.id })}>Baixar PDF</a>
                                </Button>
                                <Button
                                    variant="secondary"
                                    disabled={ocupado || !fatura.email}
                                    onClick={() => enviar(route('lab.financeiro.reenviar', { fatura: fatura.id }))}
                                >
                                    {fatura.enviadaEm ? 'Reenviar por e-mail' : 'Enviar por e-mail'}
                                </Button>
                                {fatura.emCobranca && (
                                    <>
                                        <Button disabled={ocupado} onClick={() => setRecebendo(true)}>
                                            Registrar pagamento
                                        </Button>
                                        <Button variant="ghost" disabled={ocupado} onClick={() => setCreditando(true)}>
                                            Conceder crédito
                                        </Button>
                                    </>
                                )}
                            </div>
                        </aside>
                    )}
                </div>
            </div>

            <ConfirmDialog
                open={fechando}
                onOpenChange={setFechando}
                title="Fechar o mês anterior agora?"
                description="O Vidra fecha e envia por e-mail as faturas de todos os pagadores com pedidos liberados no mês anterior, sem esperar o dia de fechamento de cada um. Quem já tem fatura do mês não recebe outra."
                confirmLabel="Fechar agora"
                loading={ocupado}
                onConfirm={() => enviar(route('lab.financeiro.fechar'), () => setFechando(false))}
            />
            {fatura && (
                <>
                    <ConfirmDialog
                        open={recebendo}
                        onOpenChange={setRecebendo}
                        title={`Registrar o pagamento de ${fatura.pagador}?`}
                        description={`Confirma que o laboratório recebeu ${reais(fatura.total)} da fatura ${fatura.numero}. Isso não se desfaz.`}
                        confirmLabel="Registrar pagamento"
                        loading={ocupado}
                        onConfirm={() => enviar(route('lab.financeiro.pagamento', { fatura: fatura.id }), () => setRecebendo(false))}
                    />
                    <ConfirmDialog
                        open={creditando}
                        onOpenChange={setCreditando}
                        title="Conceder crédito"
                        description={`O valor sai do total da fatura ${fatura.numero} e aparece no PDF. O crédito não pode passar de ${reais(fatura.total)}.`}
                        confirmLabel="Conceder crédito"
                        loading={credito.processing}
                        onConfirm={() => document.querySelector<HTMLFormElement>('#form-credito')?.requestSubmit()}
                    >
                        <form
                            id="form-credito"
                            className="space-y-4"
                            onSubmit={(e) => {
                                e.preventDefault();
                                if (!e.currentTarget.reportValidity()) return;
                                credito.post(route('lab.financeiro.credito', { fatura: fatura.id }), {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        credito.reset();
                                        setCreditando(false);
                                    },
                                });
                            }}
                        >
                            {errors.form && <Alert tone="crit" title={errors.form} />}
                            <FormField id="credito-motivo" label="Motivo" error={credito.errors.motivo}>
                                <TextInput
                                    required
                                    minLength={3}
                                    maxLength={500}
                                    value={credito.data.motivo}
                                    onChange={(e) => credito.setData('motivo', e.target.value)}
                                />
                            </FormField>
                            <FormField id="credito-valor" label="Valor em reais" hint="Com vírgula. Exemplo: 25,50" error={credito.errors.valor}>
                                <TextInput
                                    required
                                    mono
                                    inputMode="decimal"
                                    pattern="\d+(,\d{1,2})?"
                                    value={credito.data.valor}
                                    onChange={(e) => credito.setData('valor', e.target.value)}
                                />
                            </FormField>
                        </form>
                    </ConfirmDialog>
                </>
            )}
            {flash.message && <Toast message={flash.message} onDismiss={flash.dismiss} className="fixed right-4 bottom-4 z-50" />}
        </AppLayout>
    );
}
