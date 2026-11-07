import { cn } from '@/lib/utils';

const titles = new Set(['dr.', 'dra.', 'sr.', 'sra.']);

/** Duas iniciais do nome, ignorando tratamentos (Dr., Dra., Sr., Sra.). */
function initialsOf(name: string): string {
    const words = name.split(/\s+/).filter((word) => word && !titles.has(word.toLowerCase()));

    if (words.length === 0) {
        return '';
    }

    const first = words[0][0];
    const last = words.length > 1 ? words[words.length - 1][0] : (words[0][1] ?? '');

    return (first + last).toLocaleUpperCase('pt-BR');
}

export type InitialsAvatarProps = {
    name: string;
    className?: string;
};

/** Círculo com as iniciais. Decorativo: o nome sempre aparece ao lado. */
export function InitialsAvatar({ name, className }: InitialsAvatarProps) {
    return (
        <span
            aria-hidden="true"
            className={cn(
                'bg-secondary text-secondary-foreground inline-flex size-10 shrink-0 items-center justify-center rounded-full text-[13px] font-bold',
                className,
            )}
        >
            {initialsOf(name)}
        </span>
    );
}

export type LabMarkProps = {
    initials: string;
    /** Cor do laboratório, vinda do banco. */
    color?: string;
    size?: 'sm' | 'md';
    className?: string;
};

/** Quadrado com as iniciais do laboratório, na cor dele. Decorativo. */
export function LabMark({ initials, color = 'var(--color-primary)', size = 'md', className }: LabMarkProps) {
    return (
        <span
            aria-hidden="true"
            style={{ backgroundColor: color }}
            className={cn(
                'inline-flex shrink-0 items-center justify-center rounded-md font-bold text-white',
                size === 'sm' ? 'size-8 text-xs' : 'size-10 text-[13px]',
                className,
            )}
        >
            {initials}
        </span>
    );
}
