import { cn } from '@/lib/utils';
import { PawPrint } from 'lucide-react';
import { type ReactNode } from 'react';

export type AnimalCardProps = {
    name: string;
    /** Ex.: "Cão · Golden Retriever · macho · 7 anos · 32 kg". */
    summary: string;
    selected?: boolean;
    /** Aviso clínico em caixa âmbar (ex.: "Alergia a dipirona"). */
    alert?: string;
    action?: ReactNode;
    /** Se existir, o cartão inteiro vira um botão de seleção. */
    onSelect?: () => void;
    className?: string;
};

/** Cartão do animal na linha Pet. Selecionável quando recebe `onSelect`. */
export function AnimalCard({ name, summary, selected = false, alert, action, onSelect, className }: AnimalCardProps) {
    const classes = cn(
        'flex w-full flex-col gap-2 rounded-lg p-3 text-left',
        selected ? 'border-brand-500 bg-secondary border-2' : 'border-border bg-card border',
        className,
    );

    const content = (
        <>
            <span className="flex items-center gap-3">
                <span aria-hidden="true" className="bg-pet-500 text-ink-on-accent flex size-11 shrink-0 items-center justify-center rounded-full">
                    <PawPrint className="size-5" />
                </span>
                <span className="flex min-w-0 flex-1 flex-col">
                    <span className="truncate font-semibold">{name}</span>
                    <span className="text-muted-foreground text-xs">{summary}</span>
                </span>
                {action}
            </span>
            {alert && <span className="bg-warn-100 text-warn-600 rounded-sm px-2.5 py-1.5 text-xs">{alert}</span>}
        </>
    );

    if (onSelect) {
        return (
            <button
                type="button"
                aria-pressed={selected}
                onClick={onSelect}
                className={cn(classes, 'focus-visible:outline-brand-500 focus-visible:outline-2 focus-visible:outline-offset-2')}
            >
                {content}
            </button>
        );
    }

    return <div className={classes}>{content}</div>;
}
