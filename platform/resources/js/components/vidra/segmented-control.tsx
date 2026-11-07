import { cn } from '@/lib/utils';

export type SegmentedOption<T extends string> = { value: T; label: string };

export type SegmentedControlProps<T extends string> = {
    value: T;
    onChange: (value: T) => void;
    options: ReadonlyArray<SegmentedOption<T>>;
    /** Nome do grupo, lido pelo leitor de tela. */
    label: string;
    className?: string;
};

/** Alternância compacta entre visões (ex.: Lista / Quadro). Uma opção ativa por vez. */
export function SegmentedControl<T extends string>({ value, onChange, options, label, className }: SegmentedControlProps<T>) {
    return (
        <div role="group" aria-label={label} className={cn('border-input inline-flex overflow-hidden rounded-md border', className)}>
            {options.map((option, index) => {
                const active = option.value === value;

                return (
                    <button
                        key={option.value}
                        type="button"
                        aria-pressed={active}
                        onClick={() => onChange(option.value)}
                        className={cn(
                            'focus-visible:outline-brand-500 h-9 px-4 text-[13px] transition-colors focus-visible:relative focus-visible:outline-2 focus-visible:-outline-offset-2',
                            index > 0 && 'border-input border-l',
                            active ? 'bg-primary text-primary-foreground font-semibold' : 'bg-card text-foreground hover:bg-background',
                        )}
                    >
                        {option.label}
                    </button>
                );
            })}
        </div>
    );
}
