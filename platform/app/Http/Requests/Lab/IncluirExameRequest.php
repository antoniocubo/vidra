<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Vidra\Pedidos\Application\IncluirExame\IncluirExameInput;

final class IncluirExameRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['exame_id' => ['required', 'uuid']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['exame_id.required' => 'Escolha o exame para incluir.', 'exame_id.uuid' => 'Escolha um exame válido.'];
    }

    public function toInput(string $pedido): IncluirExameInput
    {
        return new IncluirExameInput($pedido, $this->string('exame_id')->value(), (int) $this->user()?->getAuthIdentifier());
    }
}
