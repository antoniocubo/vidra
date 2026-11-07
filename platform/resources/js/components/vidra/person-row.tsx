import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { InitialsAvatar } from './avatar';
import { StatusBadge, type Tone } from './status-badge';

export type PersonAccess = 'active' | 'invited' | 'none';

const accessBadge: Record<PersonAccess, { tone: Tone; label: string }> = {
    active: { tone: 'ok', label: 'Com acesso' },
    invited: { tone: 'warn', label: 'Convite enviado' },
    none: { tone: 'neutral', label: 'Sem acesso' },
};

export type PersonRowProps = {
    name: string;
    subtitle: string;
    /** Registro profissional, em mono (ex.: "CRMV-SP 41208"). */
    registry?: string;
    access?: PersonAccess;
    /** Se informado, o nome vira link. */
    href?: string;
    className?: string;
};

/** Linha de lista de pessoas (veterinários, equipe): avatar, nome, registro e acesso. */
export function PersonRow({ name, subtitle, registry, access, href, className }: PersonRowProps) {
    const badge = access ? accessBadge[access] : undefined;

    return (
        <div className={cn('border-border flex flex-wrap items-center gap-3 border-t px-4 py-3', className)}>
            <InitialsAvatar name={name} />
            <div className="flex min-w-0 flex-1 flex-col">
                {href ? (
                    <Link
                        href={href}
                        className="focus-visible:outline-brand-500 w-fit rounded-sm font-semibold hover:underline focus-visible:outline-2 focus-visible:outline-offset-2"
                    >
                        {name}
                    </Link>
                ) : (
                    <span className="font-semibold">{name}</span>
                )}
                <span className="text-muted-foreground text-xs">{subtitle}</span>
            </div>
            {registry && <span className="font-mono text-[13px]">{registry}</span>}
            {badge && (
                <StatusBadge tone={badge.tone} symbol={false}>
                    {badge.label}
                </StatusBadge>
            )}
        </div>
    );
}
