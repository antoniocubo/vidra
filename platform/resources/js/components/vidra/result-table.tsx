import { cn } from '@/lib/utils';
import { DataTable, type Column } from './data-table';
import { StatusBadge } from './status-badge';

export type ResultRowData = {
    id: string;
    parametro: string;
    valor: string;
    unidade: string;
    referencia: string;
    flag?: 'normal' | 'alto' | 'baixo' | 'critico';
    anterior?: string;
};

export type ResultTableProps = {
    rows: readonly ResultRowData[];
    /** Cabeçalho da coluna de referência, ex.: "Referência (cão adulto)". */
    referenceLabel: string;
    caption: string;
    className?: string;
};

const flagMark = {
    alto: { mark: '↑', text: 'acima da referência', cell: 'bg-warn-100 text-warn-600' },
    baixo: { mark: '↓', text: 'abaixo da referência', cell: 'bg-warn-100 text-warn-600' },
    critico: { mark: '▲', text: 'valor crítico', cell: 'bg-crit-100 text-crit-600' },
} as const;

function ResultValue({ row }: { row: ResultRowData }) {
    const flag = row.flag && row.flag !== 'normal' ? flagMark[row.flag] : undefined;

    return (
        <span className={cn('-mx-4 -my-3 block px-4 py-3 font-mono text-base font-medium tabular-nums', flag?.cell)}>
            {row.valor}
            {flag && (
                <>
                    <span aria-hidden="true"> {flag.mark}</span>
                    <span className="sr-only"> ({flag.text})</span>
                </>
            )}
        </span>
    );
}

const columnsFor = (referenceLabel: string): ReadonlyArray<Column<ResultRowData>> => [
    {
        key: 'parametro',
        header: 'Exame',
        cell: (row) => <span className={cn(row.flag === 'critico' && 'font-semibold')}>{row.parametro}</span>,
    },
    { key: 'valor', header: 'Resultado', align: 'right', cell: (row) => <ResultValue row={row} />, className: 'py-0' },
    { key: 'unidade', header: 'Unidade', className: 'text-muted-foreground', cell: (row) => row.unidade },
    { key: 'referencia', header: referenceLabel, mono: true, className: 'text-[13px] text-muted-foreground', cell: (row) => row.referencia },
    {
        key: 'historico',
        header: 'Histórico',
        cell: (row) =>
            row.anterior ? (
                <StatusBadge tone="warn" symbol={false}>
                    {row.anterior}
                </StatusBadge>
            ) : null,
    },
];

/** Resultados de exame com sinalização que não depende só de cor ou seta. */
export function ResultTable({ rows, referenceLabel, caption, className }: ResultTableProps) {
    return <DataTable className={className} caption={caption} columns={columnsFor(referenceLabel)} rows={rows} getRowId={(row) => row.id} />;
}
