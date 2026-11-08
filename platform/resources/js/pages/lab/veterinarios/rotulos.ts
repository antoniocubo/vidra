type ClinicaDoVeterinario = { id: string; nome: string; ativa: boolean };

export const ROTULO_QUEM_PAGA: Record<string, string> = { clinica: 'Clínica', veterinario: 'Veterinário', tutor: 'Tutor' };

export const ROTULO_LAUDOS: Record<string, string> = { cada_laudo: 'Cada laudo por e-mail', resumo_diario: 'Resumo do dia, às 18h' };

/** "Clínica Patas, na fatura mensal", com o nome de quem paga quando dá para saber. */
export function descreverPagador(quemPaga: string, clinicas: ClinicaDoVeterinario[]): string {
    if (quemPaga === 'veterinario') return 'O próprio veterinário, na fatura mensal';
    if (quemPaga === 'tutor') return 'O tutor, no balcão';
    return clinicas.length > 0 ? `${clinicas.map((c) => c.nome).join(', ')}, na fatura mensal` : 'A clínica, na fatura mensal';
}
