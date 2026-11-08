<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Vidra\Veterinaria\Application\CadastrarTutor\CadastrarTutorInput;

final class CadastrarTutorRequest extends FormRequest
{
    use DadosAnimalRequest;
    use DadosTutorRequest;

    protected function prepareForValidation(): void
    {
        $this->normalizarContato();
        $animais = $this->input('animais');
        if (is_array($animais)) {
            $this->merge(['animais' => array_map(fn (mixed $a): mixed => is_array($a) ? $this->normalizarAnimal($a) : $a, $animais)]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [...$this->regrasContato(), 'animais' => ['required', 'array', 'min:1', 'max:2'], ...$this->regrasAnimal('animais.*.')];
    }

    public function toInput(): CadastrarTutorInput
    {
        $animais = [];
        foreach (array_keys($this->array('animais')) as $i) {
            $animais[] = $this->animalInput('animais.'.$i.'.');
        }

        return new CadastrarTutorInput($this->string('nome')->value(), $this->string('celular')->value(), $this->opcional('email'), $this->opcional('cpf'), $this->opcional('cep'), $this->boolean('autoriza_email'), (int) $this->user()?->getAuthIdentifier(), $animais);
    }
}
