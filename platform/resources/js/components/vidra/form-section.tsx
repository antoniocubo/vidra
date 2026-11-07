import { cn } from '@/lib/utils';
import { useId, type ReactNode } from 'react';

export type FormSectionProps = {
    title: string;
    description?: string;
    className?: string;
    children: ReactNode;
};

/** Cartão que agrupa campos de um mesmo assunto, em duas colunas a partir de `sm`. */
export function FormSection({ title, description, className, children }: FormSectionProps) {
    const titleId = useId();

    return (
        <section aria-labelledby={titleId} className={cn('border-border bg-card rounded-lg border p-4', className)}>
            <h2 id={titleId} className="text-foreground text-base font-semibold">
                {title}
            </h2>
            {description && <p className="text-muted-foreground mt-1 text-xs">{description}</p>}
            <div className="mt-4 grid gap-4 sm:grid-cols-2">{children}</div>
        </section>
    );
}

export type FormActionsProps = {
    /** Ação à esquerda (ex.: "Cancelar"). */
    secondary?: ReactNode;
    className?: string;
    /** Ações à direita; o botão primário vai por último. */
    children: ReactNode;
};

/** Rodapé do formulário: ação secundária à esquerda, principal à direita. */
export function FormActions({ secondary, className, children }: FormActionsProps) {
    return (
        <div className={cn('flex flex-wrap items-center justify-between gap-3', className)}>
            <div className="flex flex-wrap items-center gap-3">{secondary}</div>
            <div className="flex flex-wrap items-center gap-3">{children}</div>
        </div>
    );
}
