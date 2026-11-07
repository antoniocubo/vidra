import { cn } from '@/lib/utils';

export type RadioCardOption<T extends string> = { value: T; label: string; description?: string };

export type RadioCardGroupProps<T extends string> = {
    name: string;
    value: T;
    onChange: (value: T) => void;
    options: ReadonlyArray<RadioCardOption<T>>;
    /** Legenda do grupo (lida pelo leitor de tela). */
    label: string;
    className?: string;
};

/** Escolha de uma opção entre poucas, em cartões com título e descrição. */
export function RadioCardGroup<T extends string>({ name, value, onChange, options, label, className }: RadioCardGroupProps<T>) {
    return (
        <fieldset className={cn('min-w-0', className)}>
            <legend className="text-muted-foreground mb-1.5 text-xs font-semibold">{label}</legend>
            <div role="radiogroup" aria-label={label} className="grid gap-3 sm:grid-cols-2">
                {options.map((option) => {
                    const selected = option.value === value;

                    return (
                        <label
                            key={option.value}
                            className={cn(
                                'has-focus-visible:outline-brand-500 flex min-h-11 cursor-pointer items-start gap-3 rounded-lg p-4 has-focus-visible:outline-2 has-focus-visible:outline-offset-2',
                                selected ? 'border-brand-500 bg-secondary border-2 p-[15px]' : 'border-border bg-card border',
                            )}
                        >
                            <input
                                type="radio"
                                name={name}
                                value={option.value}
                                checked={selected}
                                onChange={() => onChange(option.value)}
                                className="accent-primary mt-0.5 size-5 shrink-0 focus-visible:outline-none"
                            />
                            <span className="flex flex-col gap-0.5">
                                <span className="text-foreground text-sm font-semibold">{option.label}</span>
                                {option.description && <span className="text-muted-foreground text-xs">{option.description}</span>}
                            </span>
                        </label>
                    );
                })}
            </div>
        </fieldset>
    );
}
