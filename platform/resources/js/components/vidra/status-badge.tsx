import { cn } from '@/lib/utils';
import { type HTMLAttributes } from 'react';

export type Tone = 'ok' | 'warn' | 'crit' | 'info' | 'neutral';

const toneClasses: Record<Tone, string> = {
    ok: 'bg-ok-100 text-ok-600',
    warn: 'bg-warn-100 text-warn-600',
    crit: 'bg-crit-100 text-crit-600',
    info: 'bg-secondary text-secondary-foreground',
    neutral: 'border border-border text-muted-foreground',
};

/** Símbolo que acompanha a cor, para a informação não depender só dela. */
const toneSymbol: Partial<Record<Tone, string>> = { ok: '✓', crit: '▲' };

export type StatusBadgeProps = HTMLAttributes<HTMLSpanElement> & {
    /** Significado, não cor. */
    tone: Tone;
    /** Mostra ✓ (ok) ou ▲ (crit). Padrão: true. */
    symbol?: boolean;
};

/** Selo de status: par *-100 no fundo e *-600 no texto, sempre com palavra. */
export function StatusBadge({ tone, symbol = true, className, children, ...props }: StatusBadgeProps) {
    const mark = symbol ? toneSymbol[tone] : undefined;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-sm px-2 py-0.5 text-xs leading-4 font-semibold whitespace-nowrap',
                toneClasses[tone],
                className,
            )}
            {...props}
        >
            {mark && <span aria-hidden="true">{mark}</span>}
            {children}
        </span>
    );
}

/** Selo da linha de produto ("Pet", "Clínica"). Nunca usado como status. */
export function ProductBadge({ line, className }: { line: 'pet' | 'clinica'; className?: string }) {
    return (
        <span
            className={cn(
                'inline-flex rounded-sm px-2 py-0.5 text-xs leading-4 font-semibold',
                line === 'pet' ? 'bg-pet-500 text-ink-on-accent' : 'bg-clinic-500 text-white',
                className,
            )}
        >
            {line === 'pet' ? 'Pet' : 'Clínica'}
        </span>
    );
}

/** Marca o que foi preenchido pela IA e precisa de conferência humana. */
export function AiBadge({ children = 'Lido pela IA', className }: { children?: string; className?: string }) {
    return (
        <span className={cn('bg-accent-400 text-ink-on-accent inline-flex rounded-sm px-2 py-0.5 text-xs leading-4 font-semibold', className)}>
            {children}
        </span>
    );
}

/** Contador em pílula, usado em filtros de fila ("Urgentes 5"). */
export function CountPill({ label, count, active = false }: { label: string; count: number; active?: boolean }) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold',
                active ? 'bg-secondary text-secondary-foreground' : 'border-border text-muted-foreground border',
            )}
        >
            {label} <span className="font-mono tabular-nums">{count}</span>
        </span>
    );
}

/** Ponto de novidade. Sempre com texto para leitor de tela. */
export function Dot({ label, className }: { label: string; className?: string }) {
    return <span role="status" aria-label={label} className={cn('border-card bg-crit-600 inline-block size-2.5 rounded-full border-2', className)} />;
}
