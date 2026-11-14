import {
    Alert,
    Button,
    Card,
    Checkbox,
    EmptyState,
    FormField,
    PageHeader,
    StatusBadge,
    TabPanel,
    Tabs,
    TextInput,
    useFlashToast,
} from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FlaskConical } from 'lucide-react';
import { type FormEvent } from 'react';

type Faixa = { perfil: string; minimo: string | null; maximo: string | null; criticoBaixo: string | null; criticoAlto: string | null };
type Trabalho = {
    id: string;
    exame: string;
    paciente: string;
    descricao: string;
    especieCurta: string;
    perfil: string | null;
    setor: string;
    urgente: boolean;
    amostra: string;
    situacao: string;
};
type Analito = {
    id: string;
    nome: string;
    unidade: string | null;
    casas: number;
    faixa: Faixa | null;
    referencia: string;
    valor: string | null;
    texto: string | null;
    classificacao: string | null;
    anterior: { valor: string | null; texto: string | null; data: string } | null;
};
type Selecionado = { item: Trabalho; perfilDescricao: string | null; analitos: Analito[] };
type Props = { mapa: Trabalho[]; setores: Record<string, number>; setor: string; selecionado: Selecionado | null };
const SETORES: Record<string, string> = {
    hematologia: 'Hematologia',
    bioquimica: 'Bioquímica',
    urinalise: 'Urinálise',
    parasitologia: 'Parasitologia',
    hormonios: 'Hormônios',
    microbiologia: 'Microbiologia',
    outro: 'Outros',
};
const STATUS: Record<string, { texto: string; tom: 'crit' | 'warn' | 'ok' | 'neutral' }> = {
    normal: { texto: 'Normal', tom: 'ok' },
    baixo: { texto: '↓ Baixo', tom: 'warn' },
    alto: { texto: '↑ Alto', tom: 'warn' },
    critico_baixo: { texto: 'Crítico baixo', tom: 'crit' },
    critico_alto: { texto: 'Crítico alto', tom: 'crit' },
    sem_referencia: { texto: 'Sem referência', tom: 'neutral' },
    qualitativo: { texto: 'Qualitativo', tom: 'neutral' },
    invalido: { texto: 'Valor inválido', tom: 'warn' },
};
// Provisório até o laboratório definir o limite por analito.
const LIMITE_DELTA_PERCENTUAL = 50;
function escalado(v: string): number | null {
    const normal = v.trim().replace(',', '.');
    if (!/^-?\d{1,10}(\.\d{1,4})?$/.test(normal)) return null;
    return Math.round(Number(normal) * 10000);
}
function classificar(v: string, f: Faixa | null): string {
    const n = escalado(v);
    if (!v.trim()) return 'sem_referencia';
    if (n === null) return 'invalido';
    if (!f) return 'sem_referencia';
    const limite = (s: string | null) => (s === null ? null : escalado(s));
    const cb = limite(f.criticoBaixo),
        ca = limite(f.criticoAlto),
        min = limite(f.minimo),
        max = limite(f.maximo);
    if (cb !== null && n <= cb) return 'critico_baixo';
    if (ca !== null && n >= ca) return 'critico_alto';
    if (min !== null && n < min) return 'baixo';
    if (max !== null && n > max) return 'alto';
    return 'normal';
}
function Editor({ selecionado }: { selecionado: Selecionado }) {
    const { item, analitos, perfilDescricao } = selecionado;
    const form = useForm({
        valores: Object.fromEntries(analitos.map((a) => [a.id, a.valor !== null ? a.valor.replace('.', ',') : (a.texto ?? '')])) as Record<
            string,
            string
        >,
        qualitativos: Object.fromEntries(analitos.map((a) => [a.id, a.texto !== null])) as Record<string, boolean>,
    });
    const bloqueado = item.situacao !== 'em_analise';
    function salvar(event: FormEvent) {
        event.preventDefault();
        form.transform((data) => ({
            valores: Object.fromEntries(
                Object.entries(data.valores)
                    .filter(([id, v]) => v.trim() !== '' || analitos.some((a) => a.id === id && (a.valor !== null || a.texto !== null)))
                    .map(([id, v]) => [id, data.qualitativos[id] ? { valor: null, texto: v } : { valor: v || null, texto: null }]),
            ),
        }));
        form.post(route('lab.bancada.salvar', { item: item.id }), { preserveScroll: true, onSuccess: () => form.setDefaults() });
    }
    return (
        <Card title={`${item.exame} · ${item.paciente}`}>
            <p className="text-muted-foreground mb-5 text-sm">
                {item.descricao} ·{' '}
                {perfilDescricao ? `Referências para ${perfilDescricao.toLocaleLowerCase('pt-BR')}` : 'Paciente sem perfil de referência'}
            </p>
            <form onSubmit={salvar}>
                <p className="text-muted-foreground mb-3 text-xs">Tab avança · Enter salva · Resultado com letras (“Negativo”) vira texto sozinho.</p>
                {Object.values(form.errors).map((erro, i) => (
                    <p key={i} role="alert" className="text-crit-600 mb-3 text-sm">
                        {erro}
                    </p>
                ))}
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[690px] text-sm">
                        <caption className="sr-only">Resultados de {item.exame}</caption>
                        <thead className="text-muted-foreground border-b text-left text-xs">
                            <tr>
                                {['Parâmetro', 'Resultado', 'Unidade', 'Referência', 'Anterior', 'Situação'].map((t) => (
                                    <th key={t} className="px-3 py-3">
                                        {t}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {analitos.map((a) => {
                                const valor = form.data.valores[a.id] ?? '';
                                const classe = form.data.qualitativos[a.id] ? 'qualitativo' : classificar(valor, a.faixa);
                                const status = STATUS[classe];
                                const n = escalado(valor);
                                const anterior = a.anterior?.valor == null ? null : escalado(a.anterior.valor);
                                const delta =
                                    !form.data.qualitativos[a.id] &&
                                    n !== null &&
                                    anterior !== null &&
                                    (anterior === 0 ? n !== 0 : Math.abs(n - anterior) * 100 >= Math.abs(anterior) * LIMITE_DELTA_PERCENTUAL);
                                const percentual =
                                    anterior === null || anterior === 0 || n === null
                                        ? null
                                        : Math.round((Math.abs(n - anterior) / Math.abs(anterior)) * 100);
                                return (
                                    <tr
                                        key={a.id}
                                        className={`border-b ${status.tom === 'crit' ? 'bg-crit-100' : status.tom === 'warn' ? 'bg-warn-100' : ''}`}
                                    >
                                        <th className="px-3 py-3 text-left font-medium">{a.nome}</th>
                                        <td className="w-36 px-3 py-2">
                                            <FormField id={`resultado-${a.id}`} label={`Resultado de ${a.nome}`} hideLabel>
                                                <TextInput
                                                    mono
                                                    inputMode={form.data.qualitativos[a.id] ? 'text' : 'decimal'}
                                                    className="text-right"
                                                    disabled={bloqueado}
                                                    value={valor}
                                                    onChange={(e) => {
                                                        const texto = e.target.value;
                                                        // Letras no campo ("Negativo") viram resultado qualitativo sozinhas: assim
                                                        // o Tab segue de resultado em resultado sem passar pela caixa Texto.
                                                        form.setData((dados) => ({
                                                            ...dados,
                                                            valores: { ...dados.valores, [a.id]: texto },
                                                            qualitativos: /\p{L}/u.test(texto)
                                                                ? { ...dados.qualitativos, [a.id]: true }
                                                                : dados.qualitativos,
                                                        }));
                                                    }}
                                                />
                                            </FormField>
                                            <Checkbox
                                                label="Texto"
                                                aria-label={`Resultado qualitativo de ${a.nome}`}
                                                tabIndex={-1}
                                                disabled={bloqueado}
                                                checked={form.data.qualitativos[a.id]}
                                                onChange={(e) =>
                                                    form.setData('qualitativos', { ...form.data.qualitativos, [a.id]: e.target.checked })
                                                }
                                            />
                                        </td>
                                        <td className="px-3 py-3">{a.unidade ?? '—'}</td>
                                        <td className="px-3 py-3 font-mono text-xs">{a.referencia}</td>
                                        <td className="px-3 py-3 text-xs">
                                            {a.anterior ? (
                                                <>
                                                    <span className="font-mono">
                                                        {a.anterior.valor !== null ? a.anterior.valor.replace('.', ',') : (a.anterior.texto ?? '')}
                                                    </span>
                                                    <span className="text-muted-foreground block">{a.anterior.data}</span>
                                                    {delta && (
                                                        <StatusBadge tone="warn">
                                                            {percentual === null ? 'Mudou desde zero' : `Mudou ${percentual}%`} desde{' '}
                                                            {a.anterior.data}
                                                        </StatusBadge>
                                                    )}
                                                </>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td className="px-3 py-3">
                                            {valor.trim() === '' ? (
                                                <span className="text-muted-foreground">—</span>
                                            ) : (
                                                <StatusBadge tone={status.tom}>{status.texto}</StatusBadge>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
                {analitos.length === 0 && <Alert tone="warn" title="Este exame ainda não tem parâmetros cadastrados." />}
                <div className="mt-5 flex flex-wrap justify-end gap-3">
                    {bloqueado ? (
                        item.situacao === 'resultado_digitado' && (
                            <Button onClick={() => router.post(route('lab.bancada.reabrir', { item: item.id }))}>Reabrir para corrigir</Button>
                        )
                    ) : (
                        <>
                            <Button type="submit" variant="secondary" loading={form.processing}>
                                Salvar resultados
                            </Button>
                            <Button
                                disabled={form.processing || form.isDirty || analitos.length === 0}
                                title={form.isDirty ? 'Salve os resultados antes de enviar.' : undefined}
                                onClick={() =>
                                    router.post(
                                        route('lab.bancada.conferir', { item: item.id }),
                                        {},
                                        {
                                            onError: (errors) =>
                                                form.setError(
                                                    'valores',
                                                    errors.form ?? Object.values(errors)[0] ?? 'Não foi possível enviar os resultados.',
                                                ),
                                        },
                                    )
                                }
                            >
                                Enviar para conferência
                            </Button>
                        </>
                    )}
                </div>
                {form.isDirty && (
                    <p className="text-muted-foreground mt-2 text-right text-xs">Salve as alterações antes de enviar para conferência.</p>
                )}
            </form>
        </Card>
    );
}
export default function Bancada({ mapa, setores, setor, selecionado }: Props) {
    useFlashToast();
    return (
        <AppLayout breadcrumbs={[{ title: 'Bancada', href: route('lab.bancada.index') }]}>
            <Head title="Bancada" />
            <div className="min-w-0 space-y-6 p-4 md:p-8">
                <PageHeader title="Bancada" description="Resultados das amostras recebidas, por setor." />
                <Tabs
                    id="setores"
                    label="Setor"
                    value={setor}
                    onChange={(value) => router.get(route('lab.bancada.index'), { setor: value })}
                    tabs={Object.entries(setores).map(([value, count]) => ({ value, label: `${SETORES[value] ?? value} ${count}` }))}
                />
                <TabPanel tabsId="setores" value={setor}>
                    <div className="grid min-w-0 items-start gap-6 xl:grid-cols-[240px_minmax(0,1fr)]">
                        <Card title="Mapa de trabalho">
                            {mapa.length ? (
                                <ul className="divide-y">
                                    {mapa.map((i) => (
                                        <li key={i.id}>
                                            <Link
                                                className={`hover:bg-accent block rounded px-2 py-4 transition-colors ${selecionado?.item.id === i.id ? 'bg-secondary' : ''}`}
                                                href={route('lab.bancada.index', { setor, item: i.id })}
                                                aria-current={selecionado?.item.id === i.id ? 'true' : undefined}
                                            >
                                                <span className="block font-semibold">
                                                    {i.paciente} · {i.especieCurta}
                                                </span>
                                                <span className="text-muted-foreground font-mono text-xs">{i.amostra}</span>
                                                {i.urgente && (
                                                    <StatusBadge tone="crit" className="ml-2">
                                                        Urgente
                                                    </StatusBadge>
                                                )}
                                                <span className="text-muted-foreground block text-xs">{i.exame}</span>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <EmptyState
                                    icon={FlaskConical}
                                    title="Setor sem itens"
                                    description="As amostras recebidas aparecem aqui para digitação."
                                />
                            )}
                        </Card>
                        <div className="min-w-0">
                            {selecionado ? (
                                <Editor key={`${selecionado.item.id}-${JSON.stringify(selecionado.analitos)}`} selecionado={selecionado} />
                            ) : (
                                <EmptyState icon={FlaskConical} title="Nenhum item selecionado" description="Escolha um item no mapa de trabalho." />
                            )}
                        </div>
                    </div>
                </TabPanel>
            </div>
        </AppLayout>
    );
}
