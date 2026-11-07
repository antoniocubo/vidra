import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { Check, ChevronDown } from 'lucide-react';
import { LabMark } from './avatar';
import { StatusBadge } from './status-badge';

export type LabOption = {
    id: string;
    name: string;
    city: string;
    initials: string;
    color?: string;
    /** Resultados novos neste laboratório. */
    unread?: number;
    hasCritical?: boolean;
};

export type LabSwitcherProps = {
    labs: ReadonlyArray<LabOption>;
    currentId: string;
    onSelect: (id: string) => void;
    /** Se informado, mostra "Ver todos os laboratórios" no fim da lista. */
    allLabsHref?: string;
    className?: string;
};

/** Troca de laboratório. O ponto vermelho avisa que há novidade em outro laboratório. */
export function LabSwitcher({ labs, currentId, onSelect, allLabsHref, className }: LabSwitcherProps) {
    const current = labs.find((lab) => lab.id === currentId);
    const othersUnread = labs.some((lab) => lab.id !== currentId && (lab.unread ?? 0) > 0);

    if (!current) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button
                    type="button"
                    className={cn(
                        'border-border bg-card focus-visible:outline-brand-500 relative flex min-h-12 w-full items-center gap-3 rounded-lg border px-3 text-left focus-visible:outline-2 focus-visible:outline-offset-2',
                        className,
                    )}
                >
                    <LabMark initials={current.initials} color={current.color} />
                    <span className="flex min-w-0 flex-1 flex-col">
                        <span className="truncate text-sm font-semibold">{current.name}</span>
                        <span className="text-muted-foreground text-xs">trocar laboratório</span>
                    </span>
                    <ChevronDown aria-hidden="true" className="text-muted-foreground size-4 shrink-0" />
                    {othersUnread && (
                        <>
                            <span aria-hidden="true" className="border-card bg-crit-600 absolute -top-1 -right-1 size-2.5 rounded-full border-2" />
                            <span className="sr-only">Há novidades em outro laboratório</span>
                        </>
                    )}
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="w-(--radix-dropdown-menu-trigger-width) min-w-72 p-1.5">
                {labs.map((lab) => {
                    const isCurrent = lab.id === currentId;
                    const unread = lab.unread ?? 0;

                    return (
                        <DropdownMenuItem
                            key={lab.id}
                            onSelect={() => onSelect(lab.id)}
                            aria-current={isCurrent ? 'true' : undefined}
                            className={cn('min-h-12 gap-3 rounded-md px-2', isCurrent && 'bg-secondary')}
                        >
                            <LabMark initials={lab.initials} color={lab.color} size="sm" />
                            <span className="flex min-w-0 flex-1 flex-col">
                                <span className="truncate text-sm font-semibold">{lab.name}</span>
                                <span className="text-muted-foreground text-xs">{isCurrent ? 'você está aqui' : lab.city}</span>
                            </span>
                            {isCurrent ? (
                                <Check aria-hidden="true" className="text-primary size-4 shrink-0" />
                            ) : (
                                (lab.hasCritical || unread > 0) && (
                                    <StatusBadge tone={lab.hasCritical ? 'crit' : 'info'}>
                                        {lab.hasCritical ? 'Crítico' : `${unread} novos`}
                                    </StatusBadge>
                                )
                            )}
                        </DropdownMenuItem>
                    );
                })}
                {allLabsHref && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild className="text-brand-500 min-h-11 rounded-md px-2 text-sm font-semibold">
                            <Link href={allLabsHref}>Ver todos os laboratórios</Link>
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
