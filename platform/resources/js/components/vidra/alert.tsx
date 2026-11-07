import { cn } from '@/lib/utils';
import { Check, Info, TriangleAlert, type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';

export type AlertTone = 'info' | 'ok' | 'warn' | 'crit';

const toneConfig: Record<AlertTone, { box: string; icon: string; Icon: LucideIcon }> = {
    info: { box: 'bg-secondary', icon: 'text-secondary-foreground', Icon: Info },
    ok: { box: 'bg-ok-100', icon: 'text-ok-600', Icon: Check },
    warn: { box: 'bg-warn-100', icon: 'text-warn-600', Icon: TriangleAlert },
    crit: { box: 'bg-crit-100', icon: 'text-crit-600', Icon: TriangleAlert },
};

export type AlertProps = {
    tone: AlertTone;
    title: string;
    children?: ReactNode;
    /** Ação à direita (ex.: um Button). */
    action?: ReactNode;
    className?: string;
};

/** Aviso em linha. Só `crit` interrompe o leitor de tela (role="alert"). */
export function Alert({ tone, title, children, action, className }: AlertProps) {
    const { box, icon, Icon } = toneConfig[tone];

    return (
        <div role={tone === 'crit' ? 'alert' : 'status'} className={cn('flex flex-wrap items-center gap-3 rounded-lg px-4 py-3', box, className)}>
            <Icon aria-hidden="true" className={cn('size-5 shrink-0', icon)} />
            <div className="text-foreground min-w-0 flex-1 text-sm">
                <p className="font-semibold">{title}</p>
                {children && <p>{children}</p>}
            </div>
            {action}
        </div>
    );
}
