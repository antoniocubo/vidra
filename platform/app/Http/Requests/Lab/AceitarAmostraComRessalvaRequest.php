<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;

final class AceitarAmostraComRessalvaRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['ressalva' => ['required', 'string', 'max:500']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['ressalva.required' => 'Escreva a ressalva que vai para o laudo.'];
    }
}
