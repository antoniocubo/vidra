import {
    Alert,
    Button,
    ConfirmDialog,
    DataTable,
    FormField,
    PageHeader,
    PersonRow,
    RadioCardGroup,
    Select,
    StatusBadge,
    TextInput,
    useFlashToast,
} from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';

type Membro = { id: number; nome: string; email: string; papel: string; eRt: boolean };
type Convite = { id: string; nome: string; email: string; papel: string; expiraEm: string };
type Papel = { value: string; label: string };
const descricoes: Record<string, string> = {
    admin: 'Gerencia a equipe, os cadastros e todas as áreas do laboratório.',
    rt: 'Confere resultados, libera e assina laudos.',
    bancada: 'Recebe amostras e registra os resultados dos exames.',
    recepcao: 'Cadastra pacientes e solicitantes, abre pedidos e recebe amostras.',
};

export default function Equipe({
    membros,
    convites,
    papeis,
    mudancas,
}: {
    membros: Membro[];
    convites: Convite[];
    papeis: Papel[];
    mudancas: { id: string; em: string; quem: string; detalhe: string }[];
}) {
    const { auth, errors } = usePage<SharedData>().props;
    useFlashToast();
    const [convidando, setConvidando] = useState(false);
    const [removendo, setRemovendo] = useState<Membro | null>(null);
    const [cancelando, setCancelando] = useState<Convite | null>(null);
    const [ocupado, setOcupado] = useState(false);
    const conviteForm = useRef<HTMLFormElement>(null);
    const form = useForm({ nome: '', email: '', papel: 'recepcao' });
    const rotulo = (papel: string) => papeis.find((p) => p.value === papel)?.label ?? papel;
    const excluir = (url: string) => {
        setOcupado(true);
        router.delete(url, {
            preserveScroll: true,
            onSuccess: () => {
                setRemovendo(null);
                setCancelando(null);
            },
            onFinish: () => setOcupado(false),
        });
    };
    return (
        <AppLayout breadcrumbs={[{ title: 'Equipe', href: route('lab.equipe.index') }]}>
            <Head title="Equipe" />
            <div className="min-w-0 space-y-6 p-4 md:p-8">
                <PageHeader
                    title="Equipe"
                    description="Gerencie quem entra no laboratório e o que cada pessoa pode fazer."
                    actions={<Button onClick={() => setConvidando(true)}>Convidar pessoa</Button>}
                />
                {errors.form && <Alert tone="crit" title={errors.form} />}
                <DataTable<Membro>
                    caption="Pessoas com acesso ao laboratório"
                    rows={membros}
                    getRowId={(m) => String(m.id)}
                    columns={[
                        {
                            key: 'pessoa',
                            header: 'Pessoa',
                            cell: (m) => (
                                <PersonRow name={m.nome} subtitle={m.email} access="active" className="border-0 px-0 [&_span]:break-words" />
                            ),
                        },
                        {
                            key: 'papel',
                            header: 'Papel',
                            cell: (m) => (
                                <FormField id={`papel-${m.id}`} label={`Papel de ${m.nome}`} hideLabel>
                                    <Select
                                        value={m.papel}
                                        disabled={m.id === auth.user.id || ocupado}
                                        options={papeis}
                                        onChange={(papel) => {
                                            setOcupado(true);
                                            router.patch(
                                                route('lab.equipe.papel', { usuario: m.id }),
                                                { papel },
                                                { preserveScroll: true, onFinish: () => setOcupado(false) },
                                            );
                                        }}
                                    />
                                </FormField>
                            ),
                        },
                        {
                            key: 'rt',
                            header: 'Responsabilidade técnica',
                            cell: (m) => (m.eRt ? <StatusBadge tone="info">RT</StatusBadge> : <span className="text-muted-foreground">—</span>),
                        },
                        {
                            key: 'acoes',
                            header: 'Ações',
                            cell: (m) =>
                                m.id !== auth.user.id && (
                                    <Button variant="ghost" disabled={ocupado} onClick={() => setRemovendo(m)}>
                                        Remover do laboratório
                                    </Button>
                                ),
                        },
                    ]}
                />
                <h2 className="font-display text-xl font-semibold">Convites pendentes</h2>
                <DataTable<Convite>
                    caption="Convites pendentes do laboratório"
                    rows={convites}
                    getRowId={(c) => c.id}
                    emptyState={<p className="text-muted-foreground py-4">Nenhum convite pendente.</p>}
                    columns={[
                        {
                            key: 'pessoa',
                            header: 'Pessoa',
                            cell: (c) => <PersonRow name={c.nome} subtitle={c.email} access="invited" className="border-0 px-0" />,
                        },
                        { key: 'papel', header: 'Papel', cell: (c) => rotulo(c.papel) },
                        {
                            key: 'validade',
                            header: 'Validade',
                            cell: (c) => (
                                <span className="whitespace-nowrap">
                                    {new Date(c.expiraEm).getTime() <= Date.now() ? 'Expirou em' : 'Expira em'}{' '}
                                    {new Intl.DateTimeFormat('pt-BR', { timeZone: 'America/Sao_Paulo' }).format(new Date(c.expiraEm))}
                                </span>
                            ),
                        },
                        {
                            key: 'acoes',
                            header: 'Ações',
                            cell: (c) => (
                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        variant="secondary"
                                        disabled={ocupado}
                                        onClick={() => {
                                            setOcupado(true);
                                            router.post(
                                                route('lab.equipe.reenviar', { convite: c.id }),
                                                {},
                                                { preserveScroll: true, onFinish: () => setOcupado(false) },
                                            );
                                        }}
                                    >
                                        Reenviar convite
                                    </Button>
                                    <Button variant="ghost" disabled={ocupado} onClick={() => setCancelando(c)}>
                                        Cancelar convite
                                    </Button>
                                </div>
                            ),
                        },
                    ]}
                />
                <section aria-labelledby="mudancas-equipe">
                    <h2 id="mudancas-equipe" className="font-display text-xl font-semibold">
                        Últimas mudanças na equipe
                    </h2>
                    {mudancas.length === 0 ? (
                        <p className="text-muted-foreground mt-4">Nenhuma mudança registrada.</p>
                    ) : (
                        <ol className="divide-border divide-y">
                            {mudancas.map((evento) => (
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
                </section>
            </div>
            <ConfirmDialog
                open={convidando}
                onOpenChange={setConvidando}
                title="Convidar pessoa"
                description="A pessoa receberá um link por e-mail, válido por 7 dias."
                confirmLabel="Convidar pessoa"
                loading={form.processing}
                onConfirm={() => {
                    conviteForm.current?.requestSubmit();
                }}
            >
                <form
                    ref={conviteForm}
                    id="form-convite"
                    className="space-y-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        const element = e.currentTarget;
                        if (!element.reportValidity()) return;
                        form.post(route('lab.equipe.convidar'), {
                            onSuccess: () => {
                                form.reset();
                                setConvidando(false);
                            },
                        });
                    }}
                >
                    {errors.form && <Alert tone="crit" title={errors.form} />}
                    <FormField id="nome-convidado" label="Nome" error={form.errors.nome}>
                        <TextInput
                            required
                            minLength={3}
                            maxLength={255}
                            value={form.data.nome}
                            onChange={(e) => form.setData('nome', e.target.value)}
                        />
                    </FormField>
                    <FormField id="email-convidado" label="E-mail" error={form.errors.email}>
                        <TextInput
                            type="email"
                            required
                            maxLength={255}
                            value={form.data.email}
                            onChange={(e) => form.setData('email', e.target.value)}
                        />
                    </FormField>
                    <RadioCardGroup
                        name="papel"
                        label="Papel no laboratório"
                        value={form.data.papel}
                        onChange={(papel) => form.setData('papel', papel)}
                        options={papeis.map((p) => ({ ...p, description: descricoes[p.value] }))}
                    />
                    {form.errors.papel && <Alert tone="crit" title={form.errors.papel} />}
                </form>
            </ConfirmDialog>
            <ConfirmDialog
                open={removendo !== null}
                onOpenChange={(open) => {
                    if (!open) setRemovendo(null);
                }}
                title={`Remover ${removendo?.nome ?? ''} do laboratório?`}
                description="A pessoa perderá o acesso a este laboratório. Sua conta continuará existindo."
                confirmLabel="Remover do laboratório"
                tone="danger"
                loading={ocupado}
                onConfirm={() => {
                    if (removendo) excluir(route('lab.equipe.remover', { usuario: removendo.id }));
                }}
            />
            <ConfirmDialog
                open={cancelando !== null}
                onOpenChange={(open) => {
                    if (!open) setCancelando(null);
                }}
                title={`Cancelar convite de ${cancelando?.nome ?? ''}?`}
                description="O link enviado por e-mail deixará de funcionar."
                confirmLabel="Cancelar convite"
                tone="danger"
                loading={ocupado}
                onConfirm={() => {
                    if (cancelando) excluir(route('lab.equipe.cancelar', { convite: cancelando.id }));
                }}
            />
        </AppLayout>
    );
}
