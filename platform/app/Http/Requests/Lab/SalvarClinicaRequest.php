<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Vidra\Shared\Domain\ValueObjects\Cnpj;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Veterinaria\Application\Clinica\DadosDaClinicaInput;
use Vidra\Veterinaria\Domain\Clinica\FormaDePagamento;

/** Valida o formato do cadastro da clínica, para criar e para alterar. */
final class SalvarClinicaRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['cnpj', 'telefone_critico'] as $campo) {
            if ($this->filled($campo)) {
                $this->merge([$campo => preg_replace('/\D/', '', $this->string($campo)->value())]);
            }
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nome_fantasia' => ['required', 'string', 'min:2', 'max:255'],
            'razao_social' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'regex:/^\d{14}$/', function (string $attribute, mixed $value, Closure $fail): void {
                try {
                    new Cnpj((string) $value);
                } catch (InvalidValue) {
                    $fail('Informe um CNPJ válido.');
                }
            }],
            'cidade' => ['required', 'string', 'min:2', 'max:120'],
            'bairro' => ['nullable', 'string', 'max:120'],
            'email_laudos' => ['nullable', 'email', 'max:255'],
            'email_faturamento' => ['required', 'email', 'max:255'],
            'telefone_critico' => ['nullable', 'regex:/^\d{10,11}$/'],
            'responsavel' => ['nullable', 'string', 'max:255'],
            'observacao_coleta' => ['nullable', 'string', 'max:500'],
            'desconto' => ['required', 'integer', 'between:0,100'],
            'dia_fechamento' => ['required', 'integer', Rule::in([...range(1, 28), 31])],
            'dia_vencimento' => ['required', 'integer', 'between:1,28'],
            'forma_pagamento' => ['required', Rule::enum(FormaDePagamento::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'cnpj.regex' => 'Informe um CNPJ válido, com 14 dígitos.',
            'telefone_critico.regex' => 'Use DDD + número, com 10 ou 11 dígitos.',
            'dia_fechamento.in' => 'Use um dia de 1 a 28 ou o último dia do mês.',
        ];
    }

    public function toInput(): DadosDaClinicaInput
    {
        return new DadosDaClinicaInput(
            nomeFantasia: $this->string('nome_fantasia')->value(),
            cidade: $this->string('cidade')->value(),
            emailDeFaturamento: $this->string('email_faturamento')->value(),
            desconto: $this->integer('desconto'),
            diaDeFechamento: $this->integer('dia_fechamento'),
            diaDeVencimento: $this->integer('dia_vencimento'),
            formaDePagamento: $this->string('forma_pagamento')->value(),
            razaoSocial: $this->opcional('razao_social'),
            cnpj: $this->opcional('cnpj'),
            bairro: $this->opcional('bairro'),
            emailDeLaudos: $this->opcional('email_laudos'),
            telefoneCritico: $this->opcional('telefone_critico'),
            responsavel: $this->opcional('responsavel'),
            observacaoDeColeta: $this->opcional('observacao_coleta'),
        );
    }

    private function opcional(string $campo): ?string
    {
        return $this->filled($campo) ? $this->string($campo)->value() : null;
    }
}
