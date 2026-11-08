import { Alert, InitialsAvatar, VidraLogo } from '@/components/vidra';
import { type Laboratorio, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const PAPEIS: Record<string, string> = { admin: 'Administrador', rt: 'Responsável técnico', bancada: 'Bancada', recepcao: 'Recepção' };

export default function EscolherLaboratorio({ laboratorios }: { laboratorios: Laboratorio[] }) {
    const { auth, errors } = usePage<SharedData>().props;
    const [entrando, setEntrando] = useState(false);
    return (
        <div className="bg-background text-foreground min-h-screen">
            <Head title="Escolher laboratório" />
            <header className="bg-card border-border flex flex-wrap items-center justify-between gap-4 border-b px-6 py-4 md:px-10">
                <VidraLogo className="text-primary h-8" />
                <div className="flex min-w-0 items-center gap-4">
                    <div className="min-w-0 text-right text-sm">
                        <p className="font-semibold break-words">{auth.user.name}</p>
                        <p className="text-muted-foreground break-all">{auth.user.email}</p>
                    </div>
                    <Link href={route('logout')} method="post" as="button" className="text-primary min-h-11 shrink-0 font-semibold">
                        Sair
                    </Link>
                </div>
            </header>
            <main className="mx-auto flex max-w-5xl flex-col gap-8 px-6 py-12 md:py-16">
                <div className="space-y-3">
                    <h1 className="font-display text-3xl font-bold md:text-4xl">Em qual laboratório você quer entrar?</h1>
                    <p className="text-muted-foreground">
                        Sua conta tem acesso a {laboratorios.length} laboratórios. Cada um mostra só os próprios dados.
                    </p>
                </div>
                {errors.laboratorio_id && <Alert tone="crit" title={errors.laboratorio_id} />}
                <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {laboratorios.map((laboratorio) => (
                        <li key={laboratorio.id}>
                            <form
                                className="h-full"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    setEntrando(true);
                                    router.post(
                                        route('laboratorio.entrar'),
                                        { laboratorio_id: laboratorio.id },
                                        { onFinish: () => setEntrando(false) },
                                    );
                                }}
                            >
                                <button
                                    type="submit"
                                    disabled={entrando}
                                    className="bg-card border-border hover:border-primary focus-visible:outline-primary flex h-full min-h-48 w-full flex-col items-start gap-4 rounded-xl border p-6 text-left focus-visible:outline-2 focus-visible:outline-offset-2 disabled:opacity-50"
                                >
                                    <InitialsAvatar name={laboratorio.nome} />
                                    <span className="flex flex-1 flex-col gap-1">
                                        <span className="font-display text-lg font-semibold break-words">{laboratorio.nome}</span>
                                        <span className="text-muted-foreground text-sm">{PAPEIS[laboratorio.papel] ?? laboratorio.papel}</span>
                                    </span>
                                    <span className="text-primary text-sm font-semibold">Entrar →</span>
                                </button>
                            </form>
                        </li>
                    ))}
                </ul>
            </main>
        </div>
    );
}
