<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Vidra\Laudos\Application\ReabrirLaudoInput;

final class RetificarLaudoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,list<string>> */
    public function rules(): array
    {
        return ['motivo' => ['required', 'string', 'max:500']];
    }

    /** @return array<string,string> */
    public function messages(): array
    {
        return ['motivo.required' => 'Diga o motivo da retificação. Ele sai impresso no laudo.', 'motivo.max' => 'Resuma o motivo da retificação em até 500 caracteres.', 'motivo.string' => 'Diga o motivo da retificação em texto.'];
    }

    public function toInput(string $pedido): ReabrirLaudoInput
    {
        return new ReabrirLaudoInput($pedido, $this->string('motivo')->value(), (int) $this->user()?->getAuthIdentifier());
    }
}
