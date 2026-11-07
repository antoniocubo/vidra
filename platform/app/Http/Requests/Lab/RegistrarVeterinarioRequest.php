<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Vidra\Shared\Domain\ValueObjects\Uf;
use Vidra\Veterinaria\Application\RegistrarVeterinario\RegistrarVeterinarioInput;
use Vidra\Veterinaria\Domain\Veterinario\Tratamento;

/**
 * Valida o formato da entrada HTTP. Regras de negócio ficam no domínio.
 */
final class RegistrarVeterinarioRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tratamento' => ['required', Rule::enum(Tratamento::class)],
            'nome' => ['required', 'string', 'min:3', 'max:255'],
            'crmv_numero' => ['required', 'string', 'max:10'],
            'crmv_uf' => ['required', Rule::enum(Uf::class)],
            'email' => ['required', 'email', 'max:255'],
            'celular' => ['required', 'string', 'max:20'],
            'telefone_critico' => ['required', 'string', 'max:20'],
            'especialidade' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function toInput(): RegistrarVeterinarioInput
    {
        return new RegistrarVeterinarioInput(
            tratamento: $this->string('tratamento')->value(),
            nome: $this->string('nome')->value(),
            crmvNumero: $this->string('crmv_numero')->value(),
            crmvUf: $this->string('crmv_uf')->value(),
            email: $this->string('email')->value(),
            celular: $this->string('celular')->value(),
            telefoneCritico: $this->string('telefone_critico')->value(),
            especialidade: $this->filled('especialidade') ? $this->string('especialidade')->value() : null,
        );
    }
}
