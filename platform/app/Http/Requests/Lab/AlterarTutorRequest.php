<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Vidra\Veterinaria\Application\AlterarTutor\AlterarTutorInput;

final class AlterarTutorRequest extends FormRequest
{
    use DadosTutorRequest;

    protected function prepareForValidation(): void
    {
        $this->normalizarContato();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->regrasContato();
    }

    public function toInput(): AlterarTutorInput
    {
        return new AlterarTutorInput((string) $this->route('tutor'), $this->string('nome')->value(), $this->string('celular')->value(), $this->opcional('email'), $this->opcional('cpf'), $this->opcional('cep'), $this->boolean('autoriza_email'), (int) $this->user()?->getAuthIdentifier());
    }
}
