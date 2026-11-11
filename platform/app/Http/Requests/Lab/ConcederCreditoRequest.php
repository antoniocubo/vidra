<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Vidra\Financeiro\Application\ConcederCreditoInput;

final class ConcederCreditoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['motivo' => ['required', 'string', 'min:3', 'max:500'], 'valor' => ['required', 'string', 'regex:/^\d+(,\d{1,2})?$/']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['valor.regex' => 'Informe o valor em reais, com vírgula. Exemplo: 50,00.'];
    }

    /** O valor chega em reais com vírgula ("50,00") e vira centavos inteiros. */
    public function toInput(string $faturaId): ConcederCreditoInput
    {
        [$reais, $centavos] = array_pad(explode(',', $this->string('valor')->value()), 2, '');

        return new ConcederCreditoInput($faturaId, $this->string('motivo')->value(), (int) $reais * 100 + (int) str_pad($centavos, 2, '0'), (int) $this->user()?->getAuthIdentifier());
    }
}
