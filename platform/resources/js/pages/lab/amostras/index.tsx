import {
    Alert,
    Button,
    Card,
    DataTable,
    DetailList,
    EmptyState,
    FormField,
    PageHeader,
    RadioCardGroup,
    ScanField,
    StatusBadge,
    TextInput,
    Toast,
    useFlashToast,
    type Tone,
} from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Copy, TestTube } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

type Linha = {
    id: string;
    codigo: string;
    pacienteNome: string;
    especieCurta: string;
    material: string;
    pagador: string;
    situacao: string;
    motivo: string | null;
    recebidaEm: string | null;
};
type Selecionada = {
    linha: Linha;
    pedidoId: string;
    protocolo: string;
    pacienteDescricao: string;
    solicitante: string;
    exames: string[];
    mensagens: Record<string, string>;
    motivoChave: string | null;
    motivoTexto: string | null;
    ressalva: string | null;
    recoleta: string | null;
};
type Props = { data: string; recebidas: Linha[]; aguardando: Linha[]; selecionada: Selecionada | null };

const MOTIVOS = [
    { value: 'hemolise', label: 'Hemólise' },
    { value: 'volume_insuficiente', label: 'Volume insuficiente' },
    { value: 'tubo_errado', label: 'Tubo errado para o exame' },
    { value: 'coagulada', label: 'Amostra coagulada' },
    { value: 'outro', label: 'Outro motivo' },
];

function triagem(linha: Linha): { tom: Tone; rotulo: string } {
    switch (linha.situacao) {
        case 'rejeitada':
            return { tom: 'crit', rotulo: `Rejeitada · ${linha.motivo ?? ''}` };
        case 'aceita_com_ressalva':
            return { tom: 'warn', rotulo: 'Aceita com ressalva' };
        case 'aguardando':
            return { tom: 'neutral', rotulo: 'Não chegou' };
        default:
            return { tom: 'ok', rotulo: 'Aceita' };
    }
}

const paciente = (l: Linha) => (
    <>
        <strong>{l.pacienteNome}</strong> <span className="text-muted-foreground">· {l.especieCurta}</span>
    </>
);

function colunas(selecionadaId: string | undefined, comTriagem: boolean) {
    return [
        {
            key: 'codigo',
            header: 'Amostra',
            mono: true,
            cell: (l: Linha) => (
                <Link
                    href={route('lab.amostras.index', { amostra: l.id })}
                    preserveScroll
                    aria-current={l.id === selecionadaId ? 'true' : undefined}
                    className="text-brand-500 font-semibold hover:underline"
                >
                    {l.codigo}
                </Link>
            ),
        },
        { key: 'paciente', header: 'Paciente', cell: paciente },
        { key: 'material', header: 'Material', cell: (l: Linha) => l.material },
        { key: 'pagador', header: 'Pagador', cell: (l: Linha) => l.pagador },
        ...(comTriagem
            ? [{ key: 'triagem', header: 'Triagem', cell: (l: Linha) => <StatusBadge tone={triagem(l).tom}>{triagem(l).rotulo}</StatusBadge> }]
            : [{ key: 'hora', header: 'Aguardando', cell: () => <StatusBadge tone="neutral">Não chegou</StatusBadge> }]),
    ];
}

export default function Amostras({ recebidas, aguardando, selecionada }: Props) {
    const { errors } = usePage().props;
    const flash = useFlashToast();
    const areaDoCampo = useRef<HTMLDivElement>(null);
    const erro = typeof errors?.codigo === 'string' ? errors.codigo : undefined;

    const focarNoCampo = () => areaDoCampo.current?.querySelector('input')?.focus();
    useEffect(focarNoCampo, []);

    const receber = (codigo: string) =>
        router.post(route('lab.amostras.receber'), { codigo }, { preserveScroll: true, preserveState: true, onFinish: focarNoCampo });

    return (
        <AppLayout breadcrumbs={[{ title: 'Amostras', href: route('lab.amostras.index') }]}>
            <Head title="Amostras e triagem" />
            <div className="min-w-0 space-y-6 p-4 md:p-8">
                <PageHeader
                    eyebrow="Recebimento"
                    title="Amostras e triagem"
                    description="Bipe a etiqueta de cada tubo que chegar. A triagem é logo depois."
                />
                <div ref={areaDoCampo}>
                    <ScanField
                        label="Bipe a etiqueta ou digite o código"
                        placeholder="A-0000-1"
                        onScan={receber}
                        state={erro ? 'error' : flash.message ? 'success' : 'idle'}
                        message={erro ?? flash.message ?? undefined}
                    />
                </div>
                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,3fr)_minmax(320px,2fr)]">
                    <div className="min-w-0 space-y-6">
                        <Card title="Recebidas hoje" action={<span className="text-muted-foreground text-xs">{recebidas.length} no dia</span>}>
                            <DataTable
                                caption="Amostras recebidas hoje"
                                rows={recebidas}
                                getRowId={(l) => l.id}
                                selectedId={selecionada?.linha.id}
                                columns={colunas(selecionada?.linha.id, true)}
                                emptyState={<p className="text-muted-foreground text-sm">Nenhuma amostra recebida hoje. Bipe a primeira etiqueta.</p>}
                            />
                        </Card>
                        <div id="aguardando" className="scroll-mt-6">
                            <Card
                                title="Aguardando chegada"
                                action={<span className="text-muted-foreground text-xs">{aguardando.length} esperadas</span>}
                            >
                                <DataTable
                                    caption="Amostras que ainda não chegaram"
                                    rows={aguardando}
                                    getRowId={(l) => l.id}
                                    selectedId={selecionada?.linha.id}
                                    columns={colunas(selecionada?.linha.id, false)}
                                    emptyState={<p className="text-muted-foreground text-sm">Nenhuma amostra esperada no momento.</p>}
                                />
                            </Card>
                        </div>
                    </div>
                    {selecionada ? (
                        <PainelDaAmostra key={selecionada.linha.id} amostra={selecionada} />
                    ) : (
                        <EmptyState
                            icon={TestTube}
                            title="Nenhuma amostra selecionada"
                            description="Escolha um código na lista para rejeitar, pedir recoleta ou aceitar com ressalva."
                        />
                    )}
                </div>
            </div>
            {flash.message && !erro && <Toast message={flash.message} onDismiss={flash.dismiss} className="fixed right-4 bottom-4 z-50" />}
        </AppLayout>
    );
}

function PainelDaAmostra({ amostra }: { amostra: Selecionada }) {
    const { linha } = amostra;
    const { errors } = usePage().props;
    const [motivo, setMotivo] = useState(amostra.motivoChave ?? 'hemolise');
    const [texto, setTexto] = useState('');
    const [ressalva, setRessalva] = useState('');
    const [comRessalva, setComRessalva] = useState(false);
    const [copiado, setCopiado] = useState(false);
    const [enviando, setEnviando] = useState(false);
    const erroDe = (campo: string) => (typeof errors?.[campo] === 'string' ? (errors[campo] as string) : undefined);

    const detalhe = amostra.motivoTexto ?? texto;
    const mensagem = (amostra.mensagens[motivo] ?? '').replace('{texto}', detalhe.trim() === '' ? '…' : detalhe.trim().replace(/\.$/, ''));
    const enviar = (rota: string, dados: Record<string, string>) =>
        router.post(rota, dados, { preserveScroll: true, onStart: () => setEnviando(true), onFinish: () => setEnviando(false) });
    const copiar = async () => {
        try {
            await navigator.clipboard.writeText(mensagem);
            setCopiado(true);
            window.setTimeout(() => setCopiado(false), 2500);
        } catch {
            setCopiado(false);
        }
    };

    const recebida = linha.situacao === 'recebida';
    const mensagemParaCopiar = (
        <div className="bg-background rounded-lg p-3 text-sm">
            <span className="text-muted-foreground block text-xs font-semibold">Mensagem para a clínica</span>
            <p className="mt-1">{mensagem}</p>
            <Button type="button" variant="secondary" size="sm" icon={Copy} className="mt-2" onClick={copiar}>
                {copiado ? 'Mensagem copiada' : 'Copiar mensagem'}
            </Button>
        </div>
    );

    return (
        <Card className="min-w-0">
            <div className="flex flex-col gap-1">
                <span className="text-muted-foreground font-mono text-xs">
                    {linha.codigo} · {linha.material}
                </span>
                <h2 className="font-display text-xl font-bold">{linha.pacienteNome}</h2>
                <span className="text-muted-foreground text-sm">{amostra.pacienteDescricao}</span>
            </div>
            <DetailList
                items={[
                    { term: 'Pedido', value: amostra.protocolo, mono: true },
                    { term: 'Solicitante', value: amostra.solicitante },
                    { term: 'Pagador', value: linha.pagador },
                    { term: 'Exames', value: amostra.exames.join(', ') },
                    { term: 'Chegou', value: linha.recebidaEm ?? 'Ainda não', mono: true },
                ]}
            />
            {erroDe('form') && <Alert tone="crit" title={erroDe('form') ?? ''} />}

            {linha.situacao === 'aguardando' && <Alert tone="info" title="Esta amostra ainda não chegou. Bipe a etiqueta quando o tubo entrar." />}

            {linha.situacao === 'rejeitada' && (
                <>
                    <Alert tone="crit" title={`Rejeitada: ${linha.motivo ?? ''}`}>
                        {amostra.recoleta ? `A recoleta tem o código ${amostra.recoleta}.` : 'Ainda sem recoleta.'}
                    </Alert>
                    {mensagemParaCopiar}
                </>
            )}

            {linha.situacao === 'aceita_com_ressalva' && (
                <Alert tone="warn" title="Aceita com ressalva no laudo">
                    {amostra.ressalva}
                </Alert>
            )}

            {recebida && (
                <>
                    <RadioCardGroup name="motivo" label="Motivo da rejeição" value={motivo} onChange={setMotivo} options={MOTIVOS} />
                    {motivo === 'outro' && (
                        <FormField id="motivo-texto" label="Descreva o motivo" error={erroDe('texto')}>
                            <TextInput value={texto} onChange={(e) => setTexto(e.target.value)} />
                        </FormField>
                    )}
                    {erroDe('motivo') && <Alert tone="crit" title={erroDe('motivo') ?? ''} />}
                    {mensagemParaCopiar}
                    <Button
                        type="button"
                        variant="danger"
                        loading={enviando}
                        onClick={() => enviar(route('lab.amostras.rejeitar', { amostra: linha.id }), { motivo, texto })}
                    >
                        Rejeitar e pedir recoleta
                    </Button>
                    {comRessalva ? (
                        <div className="border-border flex flex-col gap-3 rounded-lg border p-3">
                            <FormField id="ressalva" label="Ressalva que vai para o laudo" error={erroDe('ressalva')}>
                                <TextInput
                                    value={ressalva}
                                    onChange={(e) => setRessalva(e.target.value)}
                                    placeholder="Ex.: amostra levemente lipêmica"
                                />
                            </FormField>
                            <Button
                                type="button"
                                variant="secondary"
                                loading={enviando}
                                onClick={() => enviar(route('lab.amostras.ressalva', { amostra: linha.id }), { ressalva })}
                            >
                                Salvar ressalva no laudo
                            </Button>
                        </div>
                    ) : (
                        <Button type="button" variant="secondary" onClick={() => setComRessalva(true)}>
                            Aceitar com ressalva no laudo
                        </Button>
                    )}
                </>
            )}
        </Card>
    );
}
