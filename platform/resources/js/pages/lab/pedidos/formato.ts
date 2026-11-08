import { type Tone } from '@/components/vidra';

/** R$ 138,00 */
export function reais(centavos: number): string {
    return (centavos / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

/** Mesma regra do servidor: arredonda para o centavo, metade para cima. */
export function comDesconto(centavos: number, percentual: number): number {
    return Math.floor((centavos * (100 - percentual) + 50) / 100);
}

export const tomDaSituacao: Record<string, Tone> = {
    aguardando_amostra: 'neutral',
    em_analise: 'info',
    aguardando_liberacao: 'warn',
    liberado: 'ok',
    cancelado: 'neutral',
};
