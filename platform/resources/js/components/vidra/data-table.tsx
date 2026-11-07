import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

export type Column<T> = {
    key: string;
    header: string;
    align?: 'left' | 'right';
    mono?: boolean;
    className?: string;
    cell: (row: T) => ReactNode;
};

export type DataTableProps<T> = {
    columns: ReadonlyArray<Column<T>>;
    rows: readonly T[];
    getRowId: (row: T) => string;
    selectedId?: string;
    rowTone?: (row: T) => 'critical' | undefined;
    /** Descrição para leitor de tela (fica invisível). */
    caption: string;
    emptyState?: ReactNode;
    toolbar?: ReactNode;
    footer?: ReactNode;
    className?: string;
};

/** Tabela de dados com linha selecionável e linha crítica. */
export function DataTable<T>({ columns, rows, getRowId, selectedId, rowTone, caption, emptyState, toolbar, footer, className }: DataTableProps<T>) {
    return (
        <div className={cn('border-border bg-card overflow-hidden rounded-lg border', className)}>
            {toolbar}
            <div className="overflow-x-auto">
                <table className="w-full border-collapse text-left text-sm">
                    <caption className="sr-only">{caption}</caption>
                    <thead>
                        <tr>
                            {columns.map((column) => (
                                <th
                                    key={column.key}
                                    scope="col"
                                    className={cn(
                                        'text-muted-foreground px-4 py-2.5 text-xs font-semibold tracking-[0.04em] uppercase',
                                        column.align === 'right' ? 'text-right' : 'text-left',
                                    )}
                                >
                                    {column.header}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 ? (
                            <tr className="border-border border-t">
                                <td colSpan={columns.length} className="px-4 py-3">
                                    {emptyState}
                                </td>
                            </tr>
                        ) : (
                            rows.map((row) => {
                                const id = getRowId(row);
                                const selected = id === selectedId;
                                const critical = rowTone?.(row) === 'critical';

                                return (
                                    <tr
                                        key={id}
                                        aria-selected={selectedId === undefined ? undefined : selected}
                                        className={cn(
                                            'border-border border-t',
                                            selected
                                                ? 'bg-secondary outline-brand-500 outline-2 -outline-offset-2'
                                                : critical
                                                  ? 'bg-crit-100'
                                                  : 'hover:bg-background',
                                        )}
                                    >
                                        {columns.map((column) => (
                                            <td
                                                key={column.key}
                                                className={cn(
                                                    'px-4 py-3',
                                                    column.align === 'right' && 'text-right',
                                                    column.mono && 'font-mono tabular-nums',
                                                    column.className,
                                                )}
                                            >
                                                {column.cell(row)}
                                            </td>
                                        ))}
                                    </tr>
                                );
                            })
                        )}
                    </tbody>
                </table>
            </div>
            {footer}
        </div>
    );
}
