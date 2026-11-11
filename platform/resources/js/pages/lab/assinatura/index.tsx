import {
    Alert,
    Button,
    ConfirmDialog,
    DataTable,
    EmptyState,
    PageHeader,
    ProgressBar,
    SegmentedControl,
    StatusBadge,
    Toast,
    useFlashToast,
    type Tone,
} from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Check, Receipt } from 'lucide-react';
import { useState } from 'react';

type Cobranca = 'mensal' | 'anual';
type Plano = { value: string; nome: string; mensal: number; anual: number; pedidos: number; usuarios: number | null; unidades: number };
type Fatura = {
    id: string;
    tipo: string;
    tipoRotulo: string;
    competencia: string | null;
    valor: number;
    situacao: string;
    situacaoRotulo: string;
    vencimento: string;
    link: string | null;
};
type Props = {
    plano: string;
    cobranca: Cobranca;
    situacao: string;
    situacaoRotulo: string;
    diasRestantes: number;
    validaAte: string;
    pagoAte: string | null;
    consumo: { mes: string; pedidos: number; limite: number; percentual: number; excedente: number; valorExcedente: number };
    cobrancas: Fatura[];
    linkPendente: string | null;
    planos: Plano[];
};

const reais = (centavos: number) =>
    new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', minimumFractionDigits: centavos % 100 === 0 ? 0 : 2 }).format(
        centavos / 100,
    );
const dataLocal = (iso: string) => new Intl.DateTimeFormat('pt-BR', { timeZone: 'America/Sao_Paulo' }).format(new Date(iso));
const dataSimples = (dia: string) => dia.split('-').reverse().join('/');
const tonsDaSituacao: Record<string, Tone> = { em_teste: 'info', ativa: 'ok', atrasada: 'warn', somente_consulta: 'crit' };
const tonsDaCobranca: Record<string, Tone> = { pendente: 'warn', paga: 'ok', vencida: 'crit', cancelada: 'neutral' };
const extras: Record<string, string[]> = {
    bancada: ['Laudo por e-mail e link do tutor', 'Fatura das clínicas'],
    laboratorio: ['Pedido lido da foto pela IA', 'Tudo do Bancada'],
    rede: ['Tudo do Laboratório'],
};

export default function Assinatura(props: Props) {
    const { plano, situacao, situacaoRotulo, diasRestantes, validaAte, consumo, cobrancas, linkPendente, planos } = props;
    const { errors } = usePage<SharedData>().props;
    const flash = useFlashToast();
    const [cobranca, setCobranca] = useState<Cobranca>(props.cobranca);
    const [escolhido, setEscolhido] = useState<Plano | null>(null);
    const [ocupado, setOcupado] = useState(false);
    const atual = planos.find((p) => p.value === plano);
    const contratado = situacao === 'ativa' || situacao === 'atrasada';

    const assinar = () => {
        if (!escolhido) return;
        setOcupado(true);
        router.post(
            route('lab.assinatura.store'),
            { plano: escolhido.value, cobranca },
            { preserveScroll: true, onSuccess: () => setEscolhido(null), onFinish: () => setOcupado(false) },
        );
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Plano e cobrança', href: route('lab.assinatura.index') }]}>
            <Head title="Plano e cobrança" />
            <div className="min-w-0 space-y-6 p-4 md:p-8">
                <PageHeader
                    title="Plano e cobrança"
                    description="Você paga por pedido, não por exame. Boleto, Pix ou cartão: a escolha é sua na hora de pagar."
                    actions={
                        linkPendente ? (
                            <Button asChild>
                                <a href={linkPendente} target="_blank" rel="noreferrer">
                                    Pagar cobrança em aberto
                                </a>
                            </Button>
                        ) : undefined
                    }
                />
                {errors.form && <Alert tone="crit" title={errors.form} />}

                <section aria-labelledby="situacao-titulo" className="border-border bg-card grid gap-6 rounded-lg border p-5 md:grid-cols-2">
                    <div className="space-y-2">
                        <h2 id="situacao-titulo" className="font-display text-lg font-semibold">
                            Situação
                        </h2>
                        <div className="flex flex-wrap items-center gap-2">
                            <StatusBadge tone={tonsDaSituacao[situacao] ?? 'neutral'}>{situacaoRotulo}</StatusBadge>
                            {contratado && atual && (
                                <span className="text-sm">
                                    Plano {atual.nome}, {props.cobranca === 'anual' ? 'anual' : 'mensal'}
                                </span>
                            )}
                        </div>
                        <p className="text-muted-foreground text-sm">
                            {situacao === 'em_teste' &&
                                `Teste com todos os recursos até ${dataLocal(validaAte)} (${diasRestantes} ${diasRestantes === 1 ? 'dia' : 'dias'}).`}
                            {situacao === 'ativa' && `Pago até ${dataLocal(validaAte)}.`}
                            {situacao === 'atrasada' && `O período pago terminou em ${dataLocal(validaAte)}. Pague para não passar para só consulta.`}
                            {situacao === 'somente_consulta' && 'Você vê e baixa todos os laudos, mas não abre pedidos novos até escolher um plano.'}
                        </p>
                    </div>
                    <ProgressBar
                        label={`Pedidos de ${consumo.mes}`}
                        value={consumo.percentual}
                        display={`${consumo.pedidos.toLocaleString('pt-BR')} de ${consumo.limite.toLocaleString('pt-BR')}`}
                        footnote={
                            consumo.excedente > 0
                                ? `${reais(consumo.valorExcedente)} de excedente previsto (${consumo.excedente.toLocaleString('pt-BR')} pedidos a R$ 0,25).`
                                : 'Pedido cancelado por inteiro não conta.'
                        }
                    />
                </section>

                <section aria-labelledby="planos-titulo" className="space-y-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2 id="planos-titulo" className="font-display text-lg font-semibold">
                            {contratado ? 'Trocar de plano' : 'Escolha um plano'}
                        </h2>
                        <SegmentedControl
                            label="Forma de cobrança"
                            value={cobranca}
                            onChange={setCobranca}
                            options={[
                                { value: 'mensal', label: 'Mensal' },
                                { value: 'anual', label: 'Anual: 2 meses grátis' },
                            ]}
                            className="[&_button]:min-h-11"
                        />
                    </div>
                    <div className="grid gap-4 lg:grid-cols-3">
                        {planos.map((p) => {
                            const igual = contratado && p.value === plano && cobranca === props.cobranca;
                            const itens = [
                                `${p.pedidos.toLocaleString('pt-BR')} pedidos por mês`,
                                p.usuarios === null ? 'Usuários ilimitados' : `${p.usuarios} usuários`,
                                ...(p.unidades > 1 ? [`Até ${p.unidades} unidades`] : []),
                                ...extras[p.value],
                            ];
                            return (
                                <article
                                    key={p.value}
                                    className={cn(
                                        'bg-card flex min-w-0 flex-col gap-3 rounded-lg border p-5',
                                        igual ? 'border-primary border-2' : 'border-border',
                                    )}
                                >
                                    <h3 className="font-display text-xl font-bold">{p.nome}</h3>
                                    <p>
                                        <span className="font-display text-primary text-3xl font-bold">
                                            {reais(cobranca === 'anual' ? p.anual / 12 : p.mensal)}
                                        </span>{' '}
                                        <span className="text-muted-foreground text-sm">por mês</span>
                                    </p>
                                    <p className="text-muted-foreground text-[13px]">
                                        {cobranca === 'anual' ? `${reais(p.anual)} por ano, à vista` : `ou ${reais(p.anual)} no plano anual`}
                                    </p>
                                    <ul className="border-border flex flex-col gap-2 border-t pt-3 text-sm">
                                        {itens.map((item) => (
                                            <li key={item} className="flex gap-2">
                                                <Check aria-hidden="true" className="text-ok-600 mt-0.5 size-4 shrink-0" />
                                                <span>{item}</span>
                                            </li>
                                        ))}
                                    </ul>
                                    <Button
                                        className="mt-auto"
                                        variant={igual ? 'secondary' : 'primary'}
                                        disabled={igual || ocupado}
                                        onClick={() => setEscolhido(p)}
                                    >
                                        {igual ? 'Seu plano atual' : contratado ? `Trocar para ${p.nome}` : `Assinar plano ${p.nome}`}
                                    </Button>
                                </article>
                            );
                        })}
                    </div>
                    <p className="text-muted-foreground text-sm">
                        Sem taxa de implantação. Passou do limite do plano, cada pedido a mais custa R$ 0,25, cobrado no início do mês seguinte.
                    </p>
                </section>

                <section aria-labelledby="cobrancas-titulo" className="space-y-3">
                    <h2 id="cobrancas-titulo" className="font-display text-lg font-semibold">
                        Cobranças
                    </h2>
                    <DataTable<Fatura>
                        caption="Últimas cobranças do Vidra"
                        rows={cobrancas}
                        getRowId={(c) => c.id}
                        emptyState={
                            <EmptyState
                                icon={Receipt}
                                title="Nenhuma cobrança ainda"
                                description="Quando você escolher um plano, a cobrança aparece aqui."
                            />
                        }
                        columns={[
                            {
                                key: 'tipo',
                                header: 'Cobrança',
                                cell: (c) => (
                                    <div>
                                        <span className="font-semibold">{c.tipoRotulo}</span>
                                        {c.competencia && (
                                            <p className="text-muted-foreground font-mono text-xs">{c.competencia.split('-').reverse().join('/')}</p>
                                        )}
                                    </div>
                                ),
                            },
                            { key: 'valor', header: 'Valor', align: 'right', mono: true, cell: (c) => reais(c.valor) },
                            { key: 'vencimento', header: 'Vence em', mono: true, cell: (c) => dataSimples(c.vencimento) },
                            {
                                key: 'situacao',
                                header: 'Situação',
                                cell: (c) => <StatusBadge tone={tonsDaCobranca[c.situacao] ?? 'neutral'}>{c.situacaoRotulo}</StatusBadge>,
                            },
                            {
                                key: 'pagar',
                                header: 'Pagamento',
                                cell: (c) =>
                                    c.link && (c.situacao === 'pendente' || c.situacao === 'vencida') ? (
                                        <a
                                            href={c.link}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-brand-500 font-semibold underline-offset-2 hover:underline"
                                        >
                                            Pagar
                                        </a>
                                    ) : (
                                        <span className="text-muted-foreground">—</span>
                                    ),
                            },
                        ]}
                    />
                </section>
            </div>

            <ConfirmDialog
                open={escolhido !== null}
                onOpenChange={(aberto) => !aberto && setEscolhido(null)}
                title={`${contratado ? 'Trocar para' : 'Assinar'} o plano ${escolhido?.nome ?? ''}?`}
                description={
                    escolhido &&
                    `${reais(cobranca === 'anual' ? escolhido.anual : escolhido.mensal)} ${cobranca === 'anual' ? 'por ano, à vista' : 'por mês'}. ${
                        situacao === 'em_teste'
                            ? 'A primeira cobrança vence no fim do teste.'
                            : 'Você recebe a cobrança agora e escolhe boleto, Pix ou cartão.'
                    }${contratado ? ' A assinatura atual é cancelada.' : ''}`
                }
                confirmLabel={contratado ? 'Trocar de plano' : 'Assinar plano'}
                loading={ocupado}
                onConfirm={assinar}
            />
            {flash.message && <Toast message={flash.message} onDismiss={flash.dismiss} className="fixed right-4 bottom-4 z-50" />}
        </AppLayout>
    );
}
