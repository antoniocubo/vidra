import { cn } from '@/lib/utils';

export type MetricCardProps = {
    label: string;
    value: string | number;
    footnote?: string;
    tone?: 'default' | 'warn' | 'crit';
    className?: string;
};

/** Número grande com rótulo e nota. `crit` pinta o cartão; `warn` só a nota. */
export function MetricCard({ label, value, footnote, tone = 'default', className }: MetricCardProps) {
    const crit = tone === 'crit';

    return (
        <div className={cn('flex flex-col gap-1 rounded-lg border p-4', crit ? 'border-crit-600 bg-crit-100' : 'border-border bg-card', className)}>
            <p className={cn('text-xs font-semibold', crit ? 'text-crit-600' : 'text-muted-foreground')}>{label}</p>
            <p className={cn('font-display text-[32px] leading-[38px] font-bold tabular-nums', crit ? 'text-crit-600' : 'text-foreground')}>
                {value}
            </p>
            {footnote && <p className={cn('text-xs', tone === 'warn' ? 'text-warn-600 font-semibold' : 'text-muted-foreground')}>{footnote}</p>}
        </div>
    );
}
