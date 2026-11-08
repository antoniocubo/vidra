<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Vidra\Laudos\Application\RegistrarAvisoInput;

final class RegistrarAvisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,list<string>> */
    public function rules(): array
    {
        return ['resultado_id' => ['required', 'uuid'], 'destinatario' => ['required', 'string', 'max:255']];
    }

    public function toInput(string $pedido): RegistrarAvisoInput
    {
        return new RegistrarAvisoInput($pedido, $this->string('resultado_id')->value(), $this->string('destinatario')->value(), (int) $this->user()?->getAuthIdentifier());
    }
}
