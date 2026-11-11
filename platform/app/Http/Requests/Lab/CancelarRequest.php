<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Vidra\Pedidos\Application\CancelarExame\CancelarExameInput;
use Vidra\Pedidos\Application\CancelarPedido\CancelarPedidoInput;

final class CancelarRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['motivo' => ['required', 'string', 'max:500']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['motivo.required' => 'Escreva o motivo do cancelamento.', 'motivo.string' => 'Escreva o motivo do cancelamento.', 'motivo.max' => 'O motivo deve ter no máximo 500 caracteres.'];
    }

    public function paraExame(string $pedido, string $item): CancelarExameInput
    {
        return new CancelarExameInput($pedido, $item, $this->string('motivo')->value(), (int) $this->user()?->getAuthIdentifier());
    }

    public function paraPedido(string $pedido): CancelarPedidoInput
    {
        $motivo = $this->string('motivo')->value();
        $por = (int) $this->user()?->getAuthIdentifier();

        return new CancelarPedidoInput($pedido, $motivo, $por);
    }
}
