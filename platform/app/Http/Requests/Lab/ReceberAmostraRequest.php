<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;

final class ReceberAmostraRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['codigo' => ['required', 'string', 'max:30']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['codigo.required' => 'Bipe a etiqueta ou digite o código da amostra.'];
    }
}
