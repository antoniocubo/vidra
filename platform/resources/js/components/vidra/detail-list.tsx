import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

export type DetailItem = { term: string; value: ReactNode; mono?: boolean };

export type DetailListProps = {
    items: ReadonlyArray<DetailItem>;
    className?: string;
};

/** Pares termo/valor (ficha do paciente, dados do laudo). */
export function DetailList({ items, className }: DetailListProps) {
    return (
        <dl className={cn('grid grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)] gap-x-3 gap-y-2.5 text-sm', className)}>
            {items.map((item) => (
                <div key={item.term} className="contents">
                    <dt className="text-muted-foreground">{item.term}</dt>
                    <dd className={cn('text-foreground', item.mono && 'font-mono tabular-nums')}>{item.value}</dd>
                </div>
            ))}
        </dl>
    );
}
