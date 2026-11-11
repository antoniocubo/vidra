import { FormField, TextInput } from '@/components/vidra';
import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { comDesconto, reais } from './formato';

export type Exame = { id: string; codigo: string; nome: string; setor: string; material: string; precoCentavos: number };

type Props = {
    exames: Exame[];
    desconto: number;
    escolhidos: string[];
    onEscolher: (exame: Exame) => void;
    error?: string;
    disabled?: boolean;
};

export function BuscaDeExame({ exames, desconto, escolhidos, onEscolher, error, disabled }: Props) {
    const [busca, setBusca] = useState('');
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    useEffect(
        () => () => {
            if (timer.current) clearTimeout(timer.current);
        },
        [],
    );
    const sugestoes = busca.trim() === '' ? [] : exames.filter((e) => !escolhidos.includes(e.id));

    return (
        <div className="relative">
            <FormField id="busca-exame" label="Incluir exame" error={error}>
                <TextInput
                    id="busca-exame"
                    className="h-11"
                    icon={Search}
                    value={busca}
                    disabled={disabled}
                    placeholder="Digite nome ou código"
                    autoComplete="off"
                    maxLength={100}
                    onChange={(event) => {
                        const termo = event.target.value;
                        setBusca(termo);
                        if (timer.current) clearTimeout(timer.current);
                        timer.current = setTimeout(() => {
                            const url = new URL(window.location.href);
                            url.searchParams.set('busca_exame', termo);
                            url.searchParams.delete('imprimir');
                            router.get(url.pathname + url.search, {}, { only: ['exames'], preserveState: true, preserveScroll: true, replace: true });
                        }, 350);
                    }}
                />
            </FormField>
            {sugestoes.length > 0 && (
                <ul className="border-border bg-card absolute z-10 mt-1 max-h-72 w-full overflow-y-auto rounded-md border p-1 shadow-lg">
                    {sugestoes.map((exame) => (
                        <li key={exame.id}>
                            <button
                                type="button"
                                disabled={disabled}
                                onClick={() => {
                                    onEscolher(exame);
                                    setBusca('');
                                }}
                                className="hover:bg-muted focus-visible:outline-brand-500 flex min-h-11 w-full items-center justify-between gap-3 rounded-md px-3 text-left text-sm focus-visible:outline-2"
                            >
                                <span>
                                    <span className="font-mono">{exame.codigo}</span> · {exame.nome}
                                    <span className="text-muted-foreground block text-xs">{exame.material}</span>
                                </span>
                                <span className="font-mono text-xs">{reais(comDesconto(exame.precoCentavos, desconto))}</span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
            {busca.trim() !== '' && sugestoes.length === 0 && (
                <p className="text-muted-foreground mt-2 text-sm">Nenhum exame disponível para essa busca.</p>
            )}
        </div>
    );
}
