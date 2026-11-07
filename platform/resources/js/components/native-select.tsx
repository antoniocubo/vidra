import { cn } from '@/lib/utils';
import { type ComponentProps } from 'react';

type NativeSelectProps = ComponentProps<'select'> & {
    options: ReadonlyArray<{ value: string; label: string }>;
};

/** Select nativo com o visual dos campos: melhor no celular e no leitor de tela. */
export default function NativeSelect({ options, className, ...props }: NativeSelectProps) {
    return (
        <select
            className={cn(
                'border-input bg-card focus-visible:ring-ring h-11 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:outline-none',
                className,
            )}
            {...props}
        >
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
    );
}
