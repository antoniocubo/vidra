<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

/** Campos do vínculo (o que é do laboratório) que o cadastro e a edição do veterinário compartilham. */
trait DadosVinculoRequest
{
    /** @return array<string, mixed> */
    private function regrasDoVinculo(): array
    {
        return [
            'telefone_critico' => ['required', 'string', 'max:20'],
            'quem_paga' => ['required', 'in:clinica,veterinario,tutor'],
            'recebe_laudo' => ['required', 'in:cada_laudo,resumo_diario'],
            've_pedidos_dos_colegas' => ['required', 'boolean'],
            'clinica_ids' => ['nullable', 'array'],
            'clinica_ids.*' => ['uuid', 'distinct'],
        ];
    }

    /** @return list<string> */
    private function clinicaIds(): array
    {
        return array_values(array_map(strval(...), $this->array('clinica_ids')));
    }
}
