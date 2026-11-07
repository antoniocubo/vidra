import { cn } from '@/lib/utils';
import { type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';

export type EmptyStateProps = {
    icon: LucideIcon;
    title: string;
    description: string;
    action?: ReactNode;
    className?: string;
};

/** Lista vazia: diz o que falta e o próximo passo, nunca só "Nenhum resultado". */
export function EmptyState({ icon: Icon, title, description, action, className }: EmptyStateProps) {
    return (
        <div className={cn('border-input flex flex-col items-center gap-3 rounded-lg border border-dashed px-6 py-10 text-center', className)}>
            <span className="bg-secondary text-secondary-foreground flex size-14 items-center justify-center rounded-full">
                <Icon aria-hidden="true" className="size-6" />
            </span>
            <div className="flex flex-col gap-1">
                <p className="font-display text-foreground text-base font-semibold">{title}</p>
                <p className="text-muted-foreground max-w-sm text-sm">{description}</p>
            </div>
            {action}
        </div>
    );
}
