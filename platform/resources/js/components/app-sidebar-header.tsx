import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger, useSidebar } from '@/components/ui/sidebar';
import { IconButton } from '@/components/vidra';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Menu } from 'lucide-react';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
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
        </header>
    );
}
