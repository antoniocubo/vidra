import { cn } from '@/lib/utils';
import { usePage } from '@inertiajs/react';
import { Check, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { IconButton } from './button';

export type ToastProps = {
    message: string;
    actionLabel?: string;
    onAction?: () => void;
    onDismiss?: () => void;
    className?: string;
};

/** Confirmação passageira. Só apresentação: a fila e o tempo ficam com quem usa. */
export function Toast({ message, actionLabel, onAction, onDismiss, className }: ToastProps) {
    return (
        <div
            role="status"
            className={cn('bg-foreground text-background flex items-center gap-3 rounded-lg py-2 pr-2 pl-4 text-sm shadow-lg', className)}
        >
            <Check aria-hidden="true" className="size-[18px] shrink-0" />
            <span className="min-w-0 flex-1">{message}</span>
            {actionLabel && (
                <button
                    type="button"
                    onClick={onAction}
                    className="bg-background/15 hover:bg-background/25 focus-visible:outline-brand-500 h-11 shrink-0 rounded-md px-3 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2"
                >
                    {actionLabel}
                </button>
            )}
            {onDismiss && (
                <IconButton
                    icon={X}
                    label="Fechar aviso"
                    variant="ghost"
                    onClick={onDismiss}
                    className="text-background hover:bg-background/15 hover:text-background"
                />
            )}
        </div>
    );
}

const FLASH_DURATION_MS = 5000;

function readFlashSuccess(props: unknown): string | null {
    if (typeof props !== 'object' || props === null || !('flash' in props)) return null;
    const flash = props.flash;
    if (typeof flash !== 'object' || flash === null || !('success' in flash)) return null;
    return typeof flash.success === 'string' && flash.success !== '' ? flash.success : null;
}

/**
 * Lê `flash.success` das props do Inertia e some sozinho após 5s.
 * Reaparece a cada nova resposta, mesmo com o mesmo texto (ex.: dois cadastros seguidos).
 * `message` é null quando não há aviso a mostrar.
 */
export function useFlashToast(): { message: string | null; dismiss: () => void } {
    const { props } = usePage();
    const incoming = readFlashSuccess(props);
    const [message, setMessage] = useState<string | null>(incoming);

    useEffect(() => {
        setMessage(incoming);
        if (incoming === null) return;

        const timer = window.setTimeout(() => setMessage(null), FLASH_DURATION_MS);
        return () => window.clearTimeout(timer);
    }, [props, incoming]);

    return { message, dismiss: () => setMessage(null) };
}
