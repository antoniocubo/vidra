import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-react';
import { forwardRef, type SelectHTMLAttributes } from 'react';
import { controlClasses, useFieldControl } from './form-field';

export type SelectOption = { value: string; label: string };

export type SelectProps = Omit<SelectHTMLAttributes<HTMLSelectElement>, 'children'> & {
    options: ReadonlyArray<SelectOption>;
};

/** Seleção nativa (funciona bem no celular), com o mesmo visual do TextInput. */
export const Select = forwardRef<HTMLSelectElement, SelectProps>(({ options, className, id, ...props }, ref) => {
    const field = useFieldControl();

    return (
        <div className="relative">
            <select
                ref={ref}
                id={id ?? field.id}
                aria-describedby={field.describedBy}
                aria-invalid={field.invalid || undefined}
                className={cn(controlClasses, 'appearance-none pr-10', className)}
                {...props}
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            <ChevronDown
                aria-hidden="true"
                className="text-muted-foreground pointer-events-none absolute top-1/2 right-3 size-[18px] -translate-y-1/2"
            />
        </div>
    );
});
Select.displayName = 'Select';
