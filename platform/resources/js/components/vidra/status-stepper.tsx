import { cn } from '@/lib/utils';
import { Check } from 'lucide-react';

export type Step = { label: string; time?: string; status: 'done' | 'current' | 'todo' };

export type StatusStepperProps = {
    /** Nome da linha do tempo para o leitor de tela (ex.: "Andamento do exame"). */
    label: string;
    steps: ReadonlyArray<Step>;
    className?: string;
};

const circleClasses: Record<Step['status'], string> = {
    done: 'bg-primary text-primary-foreground',
    current: 'border-2 border-primary bg-card text-primary',
    todo: 'border border-input text-muted-foreground',
};

const statusText: Record<Step['status'], string> = { done: 'concluída', current: 'etapa atual', todo: 'pendente' };

/** Andamento do exame em etapas: coletado, recebido, em análise, liberado. */
export function StatusStepper({ label, steps, className }: StatusStepperProps) {
    return (
        <ol aria-label={label} className={cn('flex flex-wrap gap-y-4', className)}>
            {steps.map((step, index) => (
                <li
                    key={step.label}
                    aria-current={step.status === 'current' ? 'step' : undefined}
                    className="flex min-w-[140px] flex-1 flex-col gap-2"
                >
                    <div className="flex items-center">
                        <span
                            aria-hidden="true"
                            className={cn(
                                'flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                circleClasses[step.status],
                            )}
                        >
                            {step.status === 'done' ? <Check className="size-4" /> : index + 1}
                        </span>
                        {index < steps.length - 1 && (
                            <span aria-hidden="true" className={cn('h-0.5 flex-1', step.status === 'done' ? 'bg-primary' : 'bg-border')} />
                        )}
                    </div>
                    <div className="flex flex-col gap-0.5">
                        <span className={cn('text-sm', step.status === 'current' ? 'font-semibold' : 'font-medium')}>
                            {step.label}
                            <span className="sr-only">, {statusText[step.status]}</span>
                        </span>
                        {step.time && <span className="text-muted-foreground font-mono text-xs">{step.time}</span>}
                    </div>
                </li>
            ))}
        </ol>
    );
}
