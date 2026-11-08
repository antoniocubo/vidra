import { cn } from '@/lib/utils';
import { TriangleAlert, type LucideIcon } from 'lucide-react';
import { createContext, forwardRef, useContext, type InputHTMLAttributes, type ReactNode } from 'react';

type FieldContextValue = { id: string; describedBy?: string; invalid: boolean };

const FieldContext = createContext<FieldContextValue | null>(null);

/** Liga o controle ao rótulo, à ajuda e ao erro do FormField em volta dele. */
export function useFieldControl(): Partial<FieldContextValue> {
    return useContext(FieldContext) ?? {};
}

export type FormFieldProps = {
    id: string;
    label: string;
    hint?: string;
    /** Mensagem vinda do servidor (`errors.campo` do useForm). */
    error?: string;
    optional?: boolean;
    /** Rótulo só para leitor de tela, quando o contexto visual já diz o que é (célula de tabela). */
    hideLabel?: boolean;
    className?: string;
    children: ReactNode;
};

/**
 * Rótulo sempre visível acima do controle, ajuda ou erro abaixo.
 * O controle filho recebe id, aria-describedby e aria-invalid pelo contexto.
 */
export function FormField({ id, label, hint, error, optional = false, hideLabel = false, className, children }: FormFieldProps) {
    const messageId = error || hint ? `${id}-mensagem` : undefined;

    return (
        <FieldContext.Provider value={{ id, describedBy: messageId, invalid: Boolean(error) }}>
            <div className={cn('flex min-w-0 flex-col gap-1.5', className)}>
                <label htmlFor={id} className={cn('text-muted-foreground text-xs font-semibold', hideLabel && 'sr-only')}>
                    {label}
                    {optional && <span className="font-normal"> (opcional)</span>}
                </label>
                {children}
                {error ? (
                    <p id={messageId} className="text-crit-600 flex items-center gap-1.5 text-xs font-semibold">
                        <TriangleAlert aria-hidden="true" className="size-3.5 shrink-0" />
                        {error}
                    </p>
                ) : (
                    hint && (
                        <p id={messageId} className="text-muted-foreground text-xs">
                            {hint}
                        </p>
                    )
                )}
            </div>
        </FieldContext.Provider>
    );
}

/** Classes de um controle de formulário (input, select): 44px, borda line-400, foco brand. */
export const controlClasses =
    'h-11 w-full rounded-md border border-input bg-card px-3 text-sm text-foreground placeholder:text-muted-foreground focus-visible:border-brand-500 focus-visible:ring-3 focus-visible:ring-secondary focus-visible:outline-none disabled:cursor-not-allowed disabled:border-border disabled:bg-background disabled:text-muted-foreground aria-invalid:border-crit-600 aria-invalid:ring-3 aria-invalid:ring-crit-100';

export type TextInputProps = InputHTMLAttributes<HTMLInputElement> & {
    /** JetBrains Mono: códigos, números, CRMV, telefones. */
    mono?: boolean;
    /** Ícone dentro do campo, à esquerda (ex.: busca). */
    icon?: LucideIcon;
    /** Unidade à direita: "kg", "mg/dL". */
    suffix?: string;
};

export const TextInput = forwardRef<HTMLInputElement, TextInputProps>(({ mono = false, icon: Icon, suffix, className, id, ...props }, ref) => {
    const field = useFieldControl();

    return (
        <div className="relative">
            {Icon && (
                <Icon aria-hidden="true" className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-[18px] -translate-y-1/2" />
            )}
            <input
                ref={ref}
                id={id ?? field.id}
                aria-describedby={field.describedBy}
                aria-invalid={field.invalid || undefined}
                className={cn(controlClasses, mono && 'font-mono tabular-nums', Icon && 'pl-10', suffix && 'pr-14', className)}
                {...props}
            />
            {suffix && (
                <span className="text-muted-foreground pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-[13px]">{suffix}</span>
            )}
        </div>
    );
});
TextInput.displayName = 'TextInput';
