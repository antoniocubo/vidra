<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Vidra\Veterinaria\Application\AlterarVinculo\AlterarVinculoInput;

final class AlterarVinculoRequest extends FormRequest
{
    use DadosVinculoRequest;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->regrasDoVinculo();
    }

    public function toInput(string $vinculoId): AlterarVinculoInput
    {
        return new AlterarVinculoInput(
            vinculoId: $vinculoId,
            telefoneCritico: $this->string('telefone_critico')->value(),
            quemPaga: $this->string('quem_paga')->value(),
            recebeLaudo: $this->string('recebe_laudo')->value(),
            vePedidosDosColegas: $this->boolean('ve_pedidos_dos_colegas'),
            clinicaIds: $this->clinicaIds(),
        );
    }
}
