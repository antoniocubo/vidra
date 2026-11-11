import { VidraLogo } from '@/components/vidra';
import { useTemaClaro } from '@/hooks/use-appearance';
import { Head, Link } from '@inertiajs/react';
import { type ReactNode } from 'react';

const foco = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 rounded-sm';

type Props = { titulo: string; vigencia: string; children: ReactNode };

export default function SiteLegalLayout({ titulo, vigencia, children }: Props) {
    useTemaClaro();

    return (
        <div className="bg-background text-foreground min-h-screen">
            <Head title={titulo} />
            <header className="border-border bg-card border-b">
                <div className="mx-auto flex max-w-[800px] items-center justify-between px-6 py-4">
                    <Link href={route('home')} aria-label="Vidra, página inicial" className={foco}>
                        <VidraLogo line="marca" className="h-7" />
                    </Link>
                    <Link href={route('login')} className={`${foco} text-brand-500 hover:text-primary text-sm font-semibold`}>
                        Entrar
                    </Link>
                </div>
            </header>
            <main className="mx-auto flex max-w-[800px] flex-col gap-8 px-6 py-12">
                <div className="flex flex-col gap-2">
                    <h1 className="font-display text-4xl leading-tight font-bold tracking-tight">{titulo}</h1>
                    <span className="text-muted-foreground text-sm">Em vigor desde {vigencia}</span>
                </div>
                {children}
            </main>
            <footer className="border-border bg-card border-t">
                <nav aria-label="Documentos" className="text-muted-foreground mx-auto flex max-w-[800px] flex-wrap gap-6 px-6 py-6 text-sm">
                    <Link href={route('site.privacidade')} className={`${foco} text-brand-500 hover:text-primary`}>
                        Política de privacidade
                    </Link>
                    <Link href={route('site.termos')} className={`${foco} text-brand-500 hover:text-primary`}>
                        Termos de uso
                    </Link>
                </nav>
            </footer>
        </div>
    );
}

export function Secao({ titulo, children }: { titulo: string; children: ReactNode }) {
    return (
        <section className="flex flex-col gap-3">
            <h2 className="font-display text-xl font-bold">{titulo}</h2>
            <div className="text-foreground flex flex-col gap-3 text-[15px] leading-6 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5">{children}</div>
        </section>
    );
}
