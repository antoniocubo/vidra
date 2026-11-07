import { cn } from '@/lib/utils';

export type ChipOption = { value: string; label: string };

type ChipToggleGroupBase = {
    options: ReadonlyArray<ChipOption>;
    /** Nome do grupo, lido pelo leitor de tela. */
    label: string;
    className?: string;
};

export type ChipToggleGroupProps = ChipToggleGroupBase &
    (
        | { mode: 'single'; value: string; onChange: (value: string) => void }
        | { mode: 'multiple'; value: string[]; onChange: (value: string[]) => void }
    );

/** Grupo de botões de alternar: um só (single) ou vários (multiple) marcados. */
export function ChipToggleGroup(props: ChipToggleGroupProps) {
    const { options, label, className } = props;

    const isSelected = (optionValue: string) => (props.mode === 'multiple' ? props.value.includes(optionValue) : props.value === optionValue);

    const toggle = (optionValue: string) => {
        if (props.mode === 'multiple') {
            props.onChange(props.value.includes(optionValue) ? props.value.filter((item) => item !== optionValue) : [...props.value, optionValue]);
        } else {
            props.onChange(optionValue);
        }
    };

    return (
        <div role="group" aria-label={label} className={cn('flex flex-wrap gap-2', className)}>
            {options.map((option) => {
                const selected = isSelected(option.value);
                const mark = selected ? '✓ ' : props.mode === 'multiple' ? '+ ' : '';

                return (
                    <button
                        key={option.value}
                        type="button"
                        aria-pressed={selected}
                        onClick={() => toggle(option.value)}
                        className={cn(
                            'focus-visible:outline-brand-500 h-11 rounded-md px-4 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2',
                            selected
                                ? 'border-brand-500 bg-secondary text-secondary-foreground border-2 font-semibold'
                                : 'border-input bg-card text-foreground hover:bg-background border',
                        )}
                    >
                        {mark}
                        {option.label}
                    </button>
                );
            })}
        </div>
    );
}
