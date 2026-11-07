import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { type ReactNode } from 'react';

type FormFieldProps = {
    id: string;
    label: string;
    hint?: string;
    error?: string;
    className?: string;
    children: ReactNode;
};

export default function FormField({ id, label, hint, error, className, children }: FormFieldProps) {
    return (
        <div className={['grid gap-1.5', className].filter(Boolean).join(' ')}>
            <Label htmlFor={id} className="text-muted-foreground text-xs font-semibold">
                {label}
                {hint && <span className="font-normal"> ({hint})</span>}
            </Label>
            {children}
            <InputError message={error} />
        </div>
    );
}
