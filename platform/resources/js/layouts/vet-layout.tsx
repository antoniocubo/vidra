import { LabSwitcher, VidraLogo } from '@/components/vidra';
import { useTemaClaro } from '@/hooks/use-appearance';
import { Link, router } from '@inertiajs/react';
import { type ReactNode } from 'react';

export type IdentidadeVet = { nome: string; crmv: string; email: string };
export type LaboratorioVet = { laboratorioId: string; nome: string };

export default function VetLayout({
    children,
    identidade,
    laboratorio,
    laboratorios = [],
}: {
    children: ReactNode;
    identidade: IdentidadeVet;
    laboratorio?: LaboratorioVet;
    laboratorios?: LaboratorioVet[];
}) {
    useTemaClaro();
    return (
        <div className="bg-background text-foreground min-h-screen">
            <header className="border-border bg-card border-b">
                <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-5 px-4 py-5 md:px-8">
                    <VidraLogo line="pet" className="h-7" />
                    {laboratorio && (
                        <LabSwitcher
                            className="sm:w-72"
                            currentId={laboratorio.laboratorioId}
                            labs={laboratorios.map((l) => ({
                                id: l.laboratorioId,
                                name: l.nome,
                                city: '',
                                initials: l.nome.slice(0, 2).toUpperCase(),
                            }))}
                            onSelect={(id) => router.post(route('vet.laboratorio.entrar'), { laboratorio_id: id })}
                            allLabsHref={route('vet.laboratorio.escolher')}
                        />
                    )}
                    {laboratorio && <span className="bg-secondary text-primary rounded-lg px-4 py-3 font-semibold">Meus pedidos e laudos</span>}
                    <div className="flex items-center gap-4">
                        <div className="text-right text-sm">
                            <p className="font-mono font-semibold">{identidade.nome}</p>
                            <p className="text-muted-foreground font-mono text-xs">{identidade.crmv}</p>
                        </div>
                        <Link as="button" method="post" href={route('vet.sair')} className="text-primary min-h-11 font-semibold">
                            Sair
                        </Link>
                    </div>
                </div>
            </header>
            <main className="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-8 md:px-8">{children}</main>
        </div>
    );
}
