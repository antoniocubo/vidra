import { cn } from '@/lib/utils';
import { useId } from 'react';

export type SwitchProps = {
    checked: boolean;
    onCheckedChange: (checked: boolean) => void;
    /** Texto visível ao lado; é o nome acessível do interruptor. */
    label: string;
    disabled?: boolean;
    className?: string;
};

/** Interruptor liga/desliga (52x32px) para opções que valem na hora. */
export function Switch({ checked, onCheckedChange, label, disabled = false, className }: SwitchProps) {
    const labelId = useId();

    return (
        <div className={cn('flex min-h-11 items-center gap-3', className)}>
            <button
                type="button"
                role="switch"
                aria-checked={checked}
                aria-labelledby={labelId}
                disabled={disabled}
                onClick={() => onCheckedChange(!checked)}
                className={cn(
                    'focus-visible:outline-brand-500 relative h-8 w-[52px] shrink-0 rounded-full transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-45',
                    checked ? 'bg-primary' : 'bg-input',
                )}
            >
                <span
                    aria-hidden="true"
                    className={cn('absolute top-1 left-1 size-6 rounded-full bg-white transition-transform', checked && 'translate-x-5')}
                />
            </button>
            <span id={labelId} className={cn('text-foreground text-sm', disabled && 'opacity-45')}>
                {label}
            </span>
        </div>
    );
}
