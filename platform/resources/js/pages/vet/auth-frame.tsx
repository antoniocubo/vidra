import { VidraLogo } from '@/components/vidra';
import { useTemaClaro } from '@/hooks/use-appearance';
import { Head, Link } from '@inertiajs/react';
import { type ReactNode } from 'react';

export default function AuthFrame({ title, children }: { title: string; children: ReactNode }) {
    useTemaClaro();
    return (
        <main className="bg-background text-foreground flex min-h-screen flex-col items-center justify-center gap-8 p-4">
            <Head title={title} />
            <VidraLogo line="pet" className="h-8" />
            <section className="bg-card border-border flex w-full max-w-md flex-col gap-5 rounded-xl border p-6">
                <h1 className="font-display text-3xl font-bold">{title}</h1>
                {children}
                <Link className="text-primary min-h-11 content-center font-semibold" href={route('vet.entrar')}>
                    Entrar com minha senha
                </Link>
            </section>
        </main>
    );
}
