import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { type LabNavItem } from '@/navigation/lab';
import { Link, usePage } from '@inertiajs/react';

export function NavMain({ items }: { items: LabNavItem[] }) {
    usePage(); // re-renderiza a cada navegação para o item ativo acompanhar a rota
    const { setOpenMobile } = useSidebar();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Laboratório</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    const href = item.routeName === null ? null : route(item.routeName);
                    const isActive = item.routeName !== null && route().current(item.routeName);
                    return (
                        <SidebarMenuItem key={item.title}>
                            {href === null ? (
                                <SidebarMenuButton
                                    disabled
                                    aria-disabled="true"
                                    aria-label={`${item.title}, em breve`}
                                    className="min-h-11 md:min-h-0"
                                >
                                    <item.icon aria-hidden="true" />
                                    <span className="truncate group-data-[collapsible=icon]:hidden">{item.title}</span>
                                    <span className="bg-sidebar-accent text-sidebar-accent-foreground ml-auto shrink-0 rounded px-1.5 py-0.5 text-[10px] group-data-[collapsible=icon]:hidden">
                                        em breve
                                    </span>
                                </SidebarMenuButton>
                            ) : (
                                <SidebarMenuButton asChild isActive={isActive} tooltip={item.title} className="min-h-11 md:min-h-0">
                                    <Link href={href} aria-current={isActive ? 'page' : undefined} prefetch onClick={() => setOpenMobile(false)}>
                                        <item.icon aria-hidden="true" />
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            )}
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
