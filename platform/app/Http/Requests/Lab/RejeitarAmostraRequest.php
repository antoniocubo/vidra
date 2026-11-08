<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Vidra\Pedidos\Application\RejeitarAmostra\RejeitarAmostraInput;
use Vidra\Pedidos\Domain\Amostra\MotivoDeRejeicao;

final class RejeitarAmostraRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'motivo' => ['required', Rule::enum(MotivoDeRejeicao::class)],
            'texto' => ['nullable', 'required_if:motivo,outro', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['motivo.required' => 'Escolha o motivo da rejeição.', 'texto.required_if' => 'Descreva o motivo da rejeição.'];
    }

    public function toInput(string $amostraId): RejeitarAmostraInput
    {
        return new RejeitarAmostraInput($amostraId, $this->string('motivo')->value(), $this->filled('texto') ? $this->string('texto')->value() : null);
    }
}
