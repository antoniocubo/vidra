import { cn } from '@/lib/utils';
import { useId, type ReactNode } from 'react';

export type CardProps = {
    title?: string;
    /** À direita do título (ex.: link "Ver todos"). */
    action?: ReactNode;
    footer?: ReactNode;
    className?: string;
    children: ReactNode;
};

/** Cartão: fundo card, borda e raio de 12px, sem sombra. O título nomeia a região. */
export function Card({ title, action, footer, className, children }: CardProps) {
    const titleId = useId();

    return (
        <section
            aria-labelledby={title ? titleId : undefined}
            className={cn('border-border bg-card flex flex-col gap-3 rounded-lg border p-4', className)}
        >
            {(title || action) && (
                <div className="flex items-center justify-between gap-3">
                    {title && (
                        <h2 id={titleId} className="text-base font-semibold">
                            {title}
                        </h2>
                    )}
                    {action && <div className="ml-auto shrink-0">{action}</div>}
                </div>
            )}
            {children}
            {footer && <div className="border-border border-t pt-3">{footer}</div>}
        </section>
    );
}
