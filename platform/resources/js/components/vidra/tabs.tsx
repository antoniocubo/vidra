import { cn } from '@/lib/utils';
import { useId, useRef, type KeyboardEvent, type ReactNode } from 'react';

export type TabItem<T extends string> = { value: T; label: string };

export type TabsProps<T extends string> = {
    /** Nome do grupo de abas para o leitor de tela. */
    label: string;
    value: T;
    onChange: (value: T) => void;
    tabs: ReadonlyArray<TabItem<T>>;
    /** Prefixo dos ids. Passe o mesmo `id` aos TabPanel; sem ele, é gerado. */
    id?: string;
    className?: string;
};

/**
 * Abas com ativação automática: ← → Home End movem o foco e ativam.
 * Só a aba ativa entra na ordem do Tab.
 */
export function Tabs<T extends string>({ label, value, onChange, tabs, id, className }: TabsProps<T>) {
    const generatedId = useId();
    const baseId = id ?? generatedId;
    const refs = useRef<Array<HTMLButtonElement | null>>([]);

    function handleKeyDown(event: KeyboardEvent<HTMLButtonElement>, index: number) {
        const last = tabs.length - 1;
        const target = { ArrowRight: index === last ? 0 : index + 1, ArrowLeft: index === 0 ? last : index - 1, Home: 0, End: last }[event.key];

        if (target === undefined) {
            return;
        }

        event.preventDefault();
        onChange(tabs[target].value);
        refs.current[target]?.focus();
    }

    return (
        <div role="tablist" aria-label={label} className={cn('border-border flex gap-1 overflow-x-auto border-b', className)}>
            {tabs.map((tab, index) => {
                const active = tab.value === value;

                return (
                    <button
                        key={tab.value}
                        ref={(node) => {
                            refs.current[index] = node;
                        }}
                        type="button"
                        role="tab"
                        id={`${baseId}-aba-${tab.value}`}
                        aria-selected={active}
                        aria-controls={`${baseId}-painel-${tab.value}`}
                        tabIndex={active ? 0 : -1}
                        onClick={() => onChange(tab.value)}
                        onKeyDown={(event) => handleKeyDown(event, index)}
                        className={cn(
                            'focus-visible:outline-brand-500 h-11 shrink-0 px-4 text-sm whitespace-nowrap transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2',
                            active
                                ? 'text-primary font-semibold shadow-[inset_0_-2px_0_var(--color-brand-500)]'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {tab.label}
                    </button>
                );
            })}
        </div>
    );
}

export type TabPanelProps = {
    /** O mesmo `id` passado ao Tabs. */
    tabsId: string;
    /** O valor da aba que este painel mostra. */
    value: string;
    className?: string;
    children: ReactNode;
};

/** Painel de uma aba. Renderize só o da aba ativa, ou use `hidden` nos outros. */
export function TabPanel({ tabsId, value, className, children }: TabPanelProps) {
    return (
        <div
            role="tabpanel"
            id={`${tabsId}-painel-${value}`}
            aria-labelledby={`${tabsId}-aba-${value}`}
            tabIndex={0}
            className={cn('focus-visible:outline-brand-500 focus-visible:outline-2 focus-visible:outline-offset-2', className)}
        >
            {children}
        </div>
    );
}
