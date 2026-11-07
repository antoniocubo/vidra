import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { type ReactNode } from 'react';

export type PageHeaderProps = {
    title: string;
    /** Linha curta acima do título: a seção ou o contexto. */
    eyebrow?: string;
    description?: string;
    /** Volta para a tela de origem. */
    back?: { href: string; label: string };
    /** Ações da página, à direita; descem para baixo do título no celular. */
    actions?: ReactNode;
    className?: string;
};

/** Cabeçalho de página: voltar, eyebrow, título (h1), descrição e ações. */
export function PageHeader({ title, eyebrow, description, back, actions, className }: PageHeaderProps) {
    return (
        <header className={cn('flex flex-wrap items-end justify-between gap-4', className)}>
            <div className="flex min-w-0 flex-col gap-1">
                {back && (
                    <Link
                        href={back.href}
                        className="text-brand-500 focus-visible:outline-brand-500 inline-flex w-fit items-center gap-1.5 rounded-sm text-xs font-semibold focus-visible:outline-2 focus-visible:outline-offset-2"
                    >
                        <ArrowLeft aria-hidden="true" className="size-3.5" />
                        {back.label}
                    </Link>
                )}
                {eyebrow && <p className="text-muted-foreground text-xs font-semibold tracking-[0.04em] uppercase">{eyebrow}</p>}
                <h1 className="font-display text-[32px] leading-[38px] font-bold">{title}</h1>
                {description && <p className="text-muted-foreground">{description}</p>}
            </div>
            {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
        </header>
    );
}
