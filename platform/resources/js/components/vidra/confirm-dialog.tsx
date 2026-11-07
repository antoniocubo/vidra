import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { type ReactNode } from 'react';
import { Button } from './button';

export type ConfirmDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** A pergunta, com o nome: "Liberar o laudo do Thor?". */
    title: string;
    description: ReactNode;
    /** Repete o verbo da pergunta: "Liberar e assinar", nunca "OK". */
    confirmLabel: string;
    cancelLabel?: string;
    /** `danger` para ações que não se desfazem. */
    tone?: 'default' | 'danger';
    loading?: boolean;
    onConfirm: () => void;
    /** Conteúdo extra entre a descrição e os botões (ex.: um aviso). */
    children?: ReactNode;
};

/** Confirmação de ação importante. O foco inicial vai para o botão de voltar. */
export function ConfirmDialog({
    open,
    onOpenChange,
    title,
    description,
    confirmLabel,
    cancelLabel = 'Voltar',
    tone = 'default',
    loading = false,
    onConfirm,
    children,
}: ConfirmDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                onOpenAutoFocus={(event) => {
                    event.preventDefault();
                    (event.currentTarget as HTMLElement).querySelector<HTMLElement>('[data-cancel]')?.focus();
                }}
            >
                <div className="flex flex-col gap-1.5 pr-6">
                    <DialogTitle className="font-display text-xl leading-7 font-bold tracking-normal">{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </div>
                {children}
                <DialogFooter className="gap-2 sm:space-x-0">
                    <Button data-cancel variant="secondary" onClick={() => onOpenChange(false)} disabled={loading}>
                        {cancelLabel}
                    </Button>
                    <Button variant={tone === 'danger' ? 'danger' : 'primary'} onClick={onConfirm} loading={loading}>
                        {confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
