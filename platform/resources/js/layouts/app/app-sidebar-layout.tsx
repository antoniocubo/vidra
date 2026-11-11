import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { Alert, Button } from '@/components/vidra';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

function FaixaDaAssinatura() {
    const { assinatura, laboratorio } = usePage<SharedData>().props;
    if (!assinatura?.deveAvisar) return null;

    const { situacao, diasRestantes: dias } = assinatura;
    const prazo = `${dias} ${dias === 1 ? 'dia' : 'dias'}`;
    const titulos = {
        em_teste: dias === 0 ? 'Seu teste termina hoje.' : `Faltam ${prazo} do seu teste.`,
        ativa: dias === 0 ? 'Sua assinatura vence hoje.' : `Sua assinatura vence em ${prazo}.`,
        atrasada: 'O pagamento do Vidra está atrasado.',
        somente_consulta: 'O laboratório está só em consulta.',
    };
    const textos = {
        em_teste: 'Escolha um plano para continuar abrindo pedidos.',
        ativa: 'Pague a cobrança para não interromper a abertura de pedidos.',
        atrasada: 'Regularize para continuar abrindo pedidos.',
        somente_consulta: 'Os laudos continuam disponíveis, mas não dá para abrir pedidos novos.',
    };
    const admin = laboratorio?.areas.includes('assinatura') ?? false;

    return (
        <Alert
            tone={situacao === 'somente_consulta' ? 'crit' : 'warn'}
            title={titulos[situacao]}
            className="mx-3 mt-3 md:mx-4"
            action={
                admin ? (
                    <Button asChild size="sm" variant="secondary">
                        <Link href={route('lab.assinatura.index')}>Ver planos</Link>
                    </Button>
                ) : undefined
            }
        >
            {admin ? textos[situacao] : 'Fale com o administrador do laboratório.'}
        </Alert>
    );
}

export default function AppSidebarLayout({ children, breadcrumbs = [] }: { children: React.ReactNode; breadcrumbs?: BreadcrumbItem[] }) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <FaixaDaAssinatura />
                {children}
            </AppContent>
        </AppShell>
    );
}
