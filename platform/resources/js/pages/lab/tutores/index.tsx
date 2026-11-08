import {
    Alert,
    Button,
    Card,
    ConfirmDialog,
    DetailList,
    EmptyState,
    FormField,
    PageHeader,
    StatusBadge,
    TextInput,
    Toast,
    useFlashToast,
} from '@/components/vidra';
import { AnimalCard } from '@/components/vidra/pet';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { PawPrint } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
type Tutor = {
    id: string;
    nome: string;
    celular: string;
    email: string | null;
    cpf: string | null;
    cep: string | null;
    autorizadoEm: string | null;
    autorizadoPor: string | null;
    animais: { id: string; nome: string; descricao: string; veterinario: string | null; nascimento: string; microchip: string | null }[];
};
type Props = { tutores: Tutor[]; selecionado: Tutor | null; busca: string };
export default function Tutores({ tutores, selecionado, busca }: Props) {
    const [termo, setTermo] = useState(busca);
    const [confirmar, setConfirmar] = useState(false);
    const [salvando, setSalvando] = useState(false);
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const flash = useFlashToast();
    const { errors } = usePage().props;
    useEffect(
        () => () => {
            if (timer.current) clearTimeout(timer.current);
        },
        [],
    );
    const buscar = (texto: string) => {
        setTermo(texto);
        if (timer.current) clearTimeout(timer.current);
        timer.current = setTimeout(
            () => router.get(route('lab.tutores.index'), { busca: texto }, { preserveState: true, preserveScroll: true, replace: true }),
            350,
        );
    };
    const autorizar = () => {
        if (!selecionado) return;
        router.post(
            route('lab.tutores.autorizacao.store', { tutor: selecionado.id }),
            {},
            { preserveScroll: true, onStart: () => setSalvando(true), onFinish: () => setSalvando(false) },
        );
    };
    const retirar = () => {
        if (!selecionado) return;
        router.delete(route('lab.tutores.autorizacao.destroy', { tutor: selecionado.id }), {
            preserveScroll: true,
            onStart: () => setSalvando(true),
            onFinish: () => {
                setSalvando(false);
                setConfirmar(false);
            },
        });
    };
    return (
        <AppLayout breadcrumbs={[{ title: 'Tutores e animais', href: route('lab.tutores.index') }]}>
            <Head title="Tutores e animais" />
            <div className="min-w-0 space-y-6 p-4 md:p-8">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <PageHeader eyebrow="Cadastros" title="Tutores e animais" />
                    <Button asChild>
                        <Link href={route('lab.tutores.create')}>Cadastrar tutor</Link>
                    </Button>
                </div>
                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,3fr)_minmax(320px,2fr)]">
                    <Card title="Tutores" className="min-w-0">
                        <FormField id="busca" label="Buscar tutor ou animal">
                            <TextInput value={termo} onChange={(e) => buscar(e.target.value)} placeholder="Telefone, CPF, nome do tutor ou animal" />
                        </FormField>
                        {tutores.length === 0 ? (
                            <EmptyState
                                icon={PawPrint}
                                title={busca ? 'Nenhum tutor encontrado' : 'Nenhum tutor ainda'}
                                description={busca ? 'Tente outro nome, telefone ou CPF.' : 'Cadastre o primeiro tutor e seus animais.'}
                                action={
                                    <Button asChild>
                                        <Link href={route('lab.tutores.create')}>Cadastrar tutor</Link>
                                    </Button>
                                }
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="text-muted-foreground border-border border-b text-xs">
                                        <tr>
                                            <th className="p-3">Tutor</th>
                                            <th className="p-3">Animais</th>
                                            <th className="p-3">Laudo por e-mail</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {tutores.map((t) => (
                                            <tr key={t.id} className={`border-border border-b ${selecionado?.id === t.id ? 'bg-secondary' : ''}`}>
                                                <td className="p-2">
                                                    <Link
                                                        href={route('lab.tutores.index', { busca, tutor: t.id })}
                                                        preserveState
                                                        preserveScroll
                                                        className="focus-visible:outline-primary flex min-h-11 flex-col rounded-md p-1 focus-visible:outline-2"
                                                        aria-current={selecionado?.id === t.id ? 'true' : undefined}
                                                    >
                                                        <span className="font-semibold">{t.nome}</span>
                                                        <span className="text-muted-foreground font-mono text-xs whitespace-nowrap">{t.celular}</span>
                                                    </Link>
                                                </td>
                                                <td className="p-3">{t.animais.map((a) => a.nome).join(', ')}</td>
                                                <td className="p-3">
                                                    <StatusBadge tone={t.autorizadoEm ? 'ok' : 'neutral'}>
                                                        {t.autorizadoEm ? 'Sim' : 'Não, só código'}
                                                    </StatusBadge>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </Card>
                    {selecionado && (
                        <aside aria-label={`Detalhes de ${selecionado.nome}`} className="min-w-0 space-y-4">
                            <Card
                                title={selecionado.nome}
                                action={
                                    <Button variant="secondary" asChild>
                                        <Link href={route('lab.tutores.edit', { tutor: selecionado.id })}>Editar tutor</Link>
                                    </Button>
                                }
                            >
                                <DetailList
                                    className="[&_dd]:break-all"
                                    items={[
                                        { term: 'Celular', value: selecionado.celular, mono: true },
                                        { term: 'E-mail', value: selecionado.email || 'Não informado' },
                                        { term: 'CPF', value: selecionado.cpf || 'Não informado', mono: true },
                                        { term: 'CEP', value: selecionado.cep || 'Não informado', mono: true },
                                    ]}
                                />
                                <div className={`space-y-2 rounded-md p-3 ${selecionado.autorizadoEm ? 'bg-ok-100' : 'bg-muted'}`}>
                                    <p className="font-semibold">
                                        {selecionado.autorizadoEm ? 'Autorizou receber laudos por e-mail' : 'Não autorizou envio por e-mail'}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        {selecionado.autorizadoEm
                                            ? `Registrado em ${selecionado.autorizadoEm} por ${selecionado.autorizadoPor || 'atendente'}. Pode retirar a autorização a qualquer momento.`
                                            : 'O laudo vai só para o veterinário. O tutor pode consultar com o código de acesso impresso no pedido.'}
                                    </p>
                                </div>
                                {errors.email && <Alert tone="crit" title={errors.email} />}
                                {selecionado.autorizadoEm ? (
                                    <Button variant="secondary" onClick={() => setConfirmar(true)}>
                                        Retirar autorização
                                    </Button>
                                ) : (
                                    <Button variant="secondary" disabled={!selecionado.email} loading={salvando} onClick={autorizar}>
                                        Registrar autorização de e-mail
                                    </Button>
                                )}
                                {!selecionado.email && (
                                    <p className="text-muted-foreground text-xs">
                                        Edite o tutor para informar um e-mail antes de registrar a autorização.
                                    </p>
                                )}
                            </Card>
                            <Card
                                title="Animais"
                                action={
                                    <Button variant="secondary" asChild>
                                        <Link href={route('lab.tutores.animais.create', { tutor: selecionado.id })}>+ Animal</Link>
                                    </Button>
                                }
                            >
                                {selecionado.animais.map((a) => (
                                    <div key={a.id} className="space-y-2">
                                        <AnimalCard name={a.nome} summary={a.descricao} />
                                        <p className="text-muted-foreground text-xs">
                                            Acompanha: {a.veterinario || 'Não informado'} · Nascimento: {a.nascimento}
                                            {a.microchip && ` · Microchip: ${a.microchip}`}
                                        </p>
                                    </div>
                                ))}
                            </Card>
                        </aside>
                    )}
                </div>
            </div>
            <ConfirmDialog
                open={confirmar}
                onOpenChange={setConfirmar}
                title={`Retirar a autorização de ${selecionado?.nome}?`}
                description="O tutor deixa de receber laudos por e-mail. O acesso por código continua disponível."
                confirmLabel="Retirar autorização"
                onConfirm={retirar}
                loading={salvando}
            />
            {flash.message && <Toast message={flash.message} onDismiss={flash.dismiss} className="fixed right-4 bottom-4 z-50" />}
        </AppLayout>
    );
}
