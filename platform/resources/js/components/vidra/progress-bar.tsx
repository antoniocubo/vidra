import { cn } from '@/lib/utils';

export type ProgressBarProps = {
    label: string;
    /** De 0 a 100. */
    value: number;
    /** Texto ao lado do rótulo, ex.: "3h05". */
    display: string;
    footnote?: string;
    className?: string;
};

/** Barra de progresso com o valor em texto: a barra sozinha não diz o número. */
export function ProgressBar({ label, value, display, footnote, className }: ProgressBarProps) {
    const clamped = Math.min(100, Math.max(0, value));

    return (
        <div className={cn('flex flex-col gap-1.5', className)}>
            <div className="flex items-baseline justify-between gap-3 text-sm">
                <span className="text-muted-foreground">{label}</span>
                <span className="font-mono font-medium tabular-nums">{display}</span>
            </div>
            <div
                role="progressbar"
                aria-label={label}
                aria-valuenow={clamped}
                aria-valuemin={0}
                aria-valuemax={100}
                aria-valuetext={display}
                className="bg-secondary h-2 overflow-hidden rounded-full"
            >
                <div className="bg-brand-500 h-full rounded-full" style={{ width: `${clamped}%` }} />
            </div>
            {footnote && <p className="text-muted-foreground text-xs">{footnote}</p>}
        </div>
    );
}
