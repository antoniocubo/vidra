import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

export type SampleLabelProps = {
    /** Ex.: "THOR · cão". */
    animal: string;
    /** Ex.: "Carla Mendes · Clínica Patas". */
    owner: string;
    /** Ex.: "A-0412-1". */
    code: string;
    /** Ex.: "04/11/26 08:12". */
    collectedAt: string;
    /** Ex.: "Soro". */
    material: string;
    /** Cor da tampa do tubo. Aparece só na tela, não na impressão. */
    capColor?: string;
    /** SVG do Code 128 gerado no servidor. */
    barcode?: ReactNode;
    className?: string;
};

/** Prévia da etiqueta térmica de 50×25 mm, desenhada a 2× (400×200 px). */
export function SampleLabel({ animal, owner, code, collectedAt, material, capColor, barcode, className }: SampleLabelProps) {
    return (
        <div
            role="img"
            aria-label={`Etiqueta da amostra ${code}: ${animal}, tutor ${owner}, ${material}, coletada em ${collectedAt}`}
            className={cn(
                'border-input relative flex h-[200px] w-[400px] max-w-full flex-col justify-between overflow-hidden rounded-sm border bg-white p-3 text-black',
                className,
            )}
        >
            {capColor && (
                <span
                    aria-hidden="true"
                    style={{ backgroundColor: capColor }}
                    className="absolute top-3 right-3 size-4 rounded-full border border-black/20"
                />
            )}
            <div className="flex flex-col gap-0.5 pr-6">
                <span className="truncate text-xl leading-6 font-bold">{animal}</span>
                <span className="truncate text-sm leading-4">{owner}</span>
            </div>
            <div className="flex h-[60px] items-stretch">
                {barcode ?? (
                    <span
                        aria-label="Código de barras"
                        className="h-full w-full bg-[repeating-linear-gradient(90deg,#000_0,#000_2px,#fff_2px,#fff_4px,#000_4px,#000_5px,#fff_5px,#fff_8px)]"
                    />
                )}
            </div>
            <div className="flex items-end justify-between gap-2 font-mono text-sm leading-4">
                <span className="font-bold">{code}</span>
                <span>
                    {material} · {collectedAt}
                </span>
            </div>
        </div>
    );
}
