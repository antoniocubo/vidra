import { cn } from '@/lib/utils';
import { forwardRef, type InputHTMLAttributes } from 'react';

export type CheckboxProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> & {
    label: string;
    description?: string;
};

/** Caixa de marcar nativa; o rótulo inteiro é área de clique de 44px no mínimo. */
export const Checkbox = forwardRef<HTMLInputElement, CheckboxProps>(({ label, description, className, ...props }, ref) => (
    <label className={cn('flex min-h-11 cursor-pointer items-start gap-3 py-2.5 has-disabled:cursor-not-allowed has-disabled:opacity-45', className)}>
        <input
            ref={ref}
            type="checkbox"
            className="accent-primary focus-visible:outline-brand-500 mt-0.5 size-5 shrink-0 focus-visible:outline-2 focus-visible:outline-offset-2"
            {...props}
        />
        <span className="flex flex-col gap-0.5">
            <span className="text-foreground text-sm">{label}</span>
            {description && <span className="text-muted-foreground text-xs">{description}</span>}
        </span>
    </label>
));
Checkbox.displayName = 'Checkbox';
