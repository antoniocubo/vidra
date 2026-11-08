<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Closure;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Veterinaria\Domain\Tutor\Cpf;

trait DadosTutorRequest
{
    private function normalizarContato(): void
    {
        foreach (['celular', 'cpf', 'cep'] as $campo) {
            if ($this->filled($campo)) {
                $this->merge([$campo => preg_replace('/\D/', '', $this->string($campo)->value())]);
            }
        }
    }

    /** @return array<string, mixed> */
    private function regrasContato(): array
    {
        return [
            'nome' => ['required', 'string', 'min:3', 'max:255'], 'celular' => ['required', 'regex:/^[1-9]{2}9\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'cpf' => ['nullable', 'regex:/^\d{11}$/', function (string $attribute, mixed $value, Closure $fail): void {
                try {
                    new Cpf((string) $value);
                } catch (InvalidValue $e) {
                    $fail($e->getMessage());
                }
            }],
            'cep' => ['nullable', 'regex:/^\d{8}$/'], 'autoriza_email' => ['required', 'boolean'],
        ];
    }

    private function opcional(string $campo): ?string
    {
        return $this->filled($campo) ? $this->string($campo)->value() : null;
    }
}
