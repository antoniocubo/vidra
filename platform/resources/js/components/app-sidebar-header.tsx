import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger, useSidebar } from '@/components/ui/sidebar';
import { IconButton, LabSwitcher } from '@/components/vidra';
import { useInitials } from '@/hooks/use-initials';
import { type BreadcrumbItem as BreadcrumbItemType, type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const { laboratorio, laboratorios } = usePage<SharedData>().props;
    const iniciais = useInitials();
    const { isMobile, openMobile, setOpenMobile, mobileMenuId, mobileTriggerRef } = useSidebar();
    return (
        <header className="border-sidebar-border/50 flex h-16 shrink-0 items-center gap-2 border-b px-3 transition-[width,height] ease-linear md:px-4 md:group-has-data-[collapsible=icon]/sidebar-wrapper:h-12">
            {isMobile ? (
                <IconButton
                    ref={mobileTriggerRef}
                    icon={Menu}
                    variant="ghost"
                    label="Abrir menu"
                    aria-expanded={openMobile}
                    aria-controls={mobileMenuId}
                    onClick={() => setOpenMobile(true)}
                />
            ) : (
                <SidebarTrigger className="-ml-1" aria-label="Recolher menu" />
            )}
            <div className="min-w-0 flex-1">
                <Breadcrumbs breadcrumbs={isMobile ? breadcrumbs.slice(-1) : breadcrumbs} />
            </div>
            {laboratorio && (
                <div className="max-w-[55%] min-w-0 sm:max-w-xs">
                    {laboratorios.length > 1 ? (
                        <LabSwitcher
                            currentId={laboratorio.id}
                            labs={laboratorios.map((lab) => ({ id: lab.id, name: lab.nome, city: '', initials: iniciais(lab.nome) }))}
                            onSelect={(id) => {
                                if (id !== laboratorio.id) router.post(route('laboratorio.entrar'), { laboratorio_id: id });
                            }}
                        />
                    ) : (
                        <span className="block truncate text-sm font-semibold">{laboratorio.nome}</span>
                    )}
                </div>
            )}
        </header>
    );
}
