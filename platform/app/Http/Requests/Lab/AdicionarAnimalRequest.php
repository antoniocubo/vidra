<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Vidra\Veterinaria\Application\AdicionarAnimal\AdicionarAnimalInput;

final class AdicionarAnimalRequest extends FormRequest
{
    use DadosAnimalRequest;

    protected function prepareForValidation(): void
    {
        $this->merge($this->normalizarAnimal($this->all()));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->regrasAnimal('');
    }

    public function toInput(): AdicionarAnimalInput
    {
        return new AdicionarAnimalInput((string) $this->route('tutor'), $this->animalInput(''));
    }
}
