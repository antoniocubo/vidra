import { cn } from '@/lib/utils';
import { Check, ScanBarcode, TriangleAlert } from 'lucide-react';
import { useId, useRef, type KeyboardEvent } from 'react';

export type ScanState = 'idle' | 'success' | 'error';

export type ScanFieldProps = {
    /** Chamado no Enter (o leitor USB termina a leitura com Enter), já em maiúsculas e sem espaços. */
    onScan: (code: string) => void;
    state: ScanState;
    message?: string;
    label?: string;
    placeholder?: string;
    className?: string;
};

const containerClasses: Record<ScanState, string> = {
    idle: 'border-2 border-dashed border-brand-500 bg-secondary',
    success: 'border-2 border-ok-600 bg-ok-100',
    error: 'border-2 border-crit-600 bg-crit-100',
};

const messageClasses: Record<ScanState, string> = {
    idle: 'text-muted-foreground',
    success: 'text-ok-600',
    error: 'text-crit-600',
};

/** Campo da triagem: mantém o foco, lê o código no Enter, limpa e fica pronto para o próximo tubo. */
export function ScanField({
    onScan,
    state,
    message,
    label = 'Código da amostra',
    placeholder = 'Bipe ou digite: A-0412-1',
    className,
}: ScanFieldProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const id = useId();
    const messageId = `${id}-mensagem`;
    const text = message ?? (state === 'idle' ? 'Pronto para ler. Bipe a etiqueta do tubo.' : undefined);

    const handleKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();

        const input = event.currentTarget;
        const code = input.value.trim().toUpperCase();
        if (!code) return;

        onScan(code);
        input.value = '';
        input.focus();
    };

    return (
        <div className={cn('flex flex-col gap-2 rounded-lg p-4', containerClasses[state], className)}>
            <label htmlFor={id} className="text-muted-foreground text-xs font-semibold">
                {label}
            </label>
            <div className="relative">
                <ScanBarcode
                    aria-hidden="true"
                    className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-6 -translate-y-1/2"
                />
                <input
                    ref={inputRef}
                    id={id}
                    type="text"
                    autoFocus
                    autoComplete="off"
                    autoCapitalize="characters"
                    spellCheck={false}
                    placeholder={placeholder}
                    aria-describedby={text ? messageId : undefined}
                    aria-invalid={state === 'error' || undefined}
                    onKeyDown={handleKeyDown}
                    className="border-input bg-card text-foreground placeholder:text-muted-foreground focus-visible:border-brand-500 focus-visible:ring-secondary h-14 w-full rounded-md border pr-3 pl-12 font-mono text-lg tracking-wide focus-visible:ring-3 focus-visible:outline-none"
                />
            </div>
            {text && (
                <p
                    id={messageId}
                    role={state === 'error' ? 'alert' : 'status'}
                    className={cn('flex items-center gap-1.5 text-sm font-semibold', messageClasses[state])}
                >
                    {state === 'error' ? (
                        <TriangleAlert aria-hidden="true" className="size-4 shrink-0" />
                    ) : (
                        <Check aria-hidden="true" className="size-4 shrink-0" />
                    )}
                    {text}
                </p>
            )}
        </div>
    );
}
