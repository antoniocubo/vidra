<?php

declare(strict_types=1);

namespace App\Http\Requests\Acesso;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Vidra\Acesso\Application\RegistrarLaboratorio\RegistrarLaboratorioInput;
use Vidra\Acesso\Domain\Cobranca;
use Vidra\Acesso\Domain\Plano;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Shared\Domain\ValueObjects\Uf;

final class RegistrarLaboratorioRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $telefone = static function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value)) {
                return;
            }
            try {
                new Telefone($value);
            } catch (InvalidValue) {
                $fail('Informe DDD + número, com 10 ou 11 dígitos.');
            }
        };

        return [
            'laboratorio_nome' => ['required', 'string', 'min:3', 'max:255'],
            'cnpj' => ['required', 'string', 'max:18'], 'telefone' => ['required', 'string', 'max:20', $telefone],
            'cep' => ['required', 'regex:/^\d{5}-?\d{3}$/'], 'cidade' => ['required', 'string', 'max:255'],
            'uf' => ['required', Rule::enum(Uf::class)], 'endereco' => ['required', 'string', 'max:255'],
            'linha' => ['required', Rule::in(['pet'])], 'plano' => ['required', Rule::enum(Plano::class)],
            'cobranca' => ['required', Rule::enum(Cobranca::class)], 'sou_rt' => ['required', 'boolean'],
            'rt_nome' => ['required', 'string', 'min:3', 'max:255'], 'rt_numero' => ['required', 'string', 'regex:/^[a-zA-Z0-9-]{1,20}$/'],
            'rt_uf' => ['required', Rule::enum(Uf::class)], 'rt_titulo' => ['nullable', 'string', 'max:255'],
            'rt_email' => ['exclude_if:sou_rt,true', 'required_if:sou_rt,false', 'nullable', 'email', 'max:255'],
            'nome' => ['required', 'string', 'min:3', 'max:255'], 'email' => ['required', 'email', 'max:255'],
            'celular' => ['required', 'string', 'max:20', $telefone], 'senha' => ['required', 'string', 'min:10', 'max:72'],
            'termos' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['required' => 'Preencha este campo.', 'required_if' => 'Preencha este campo.', 'email' => 'Informe um e-mail válido.', 'min.string' => 'Use pelo menos :min caracteres.', 'max.string' => 'Use no máximo :max caracteres.', 'regex' => 'Confira o formato deste campo.', 'linha.in' => 'A linha humana estará disponível em breve.', 'termos.accepted' => 'Aceite os termos de uso e a política de privacidade.'];
    }

    public function toInput(): RegistrarLaboratorioInput
    {
        return new RegistrarLaboratorioInput(
            laboratorioNome: $this->string('laboratorio_nome')->value(), cnpj: $this->string('cnpj')->value(), telefone: $this->string('telefone')->value(),
            cep: $this->string('cep')->value(), cidade: $this->string('cidade')->value(), uf: $this->string('uf')->value(), endereco: $this->string('endereco')->value(),
            linha: $this->string('linha')->value(), plano: $this->string('plano')->value(), cobranca: $this->string('cobranca')->value(),
            rtNome: $this->string('rt_nome')->value(), rtConselho: 'CRMV', rtNumero: $this->string('rt_numero')->value(), rtUf: $this->string('rt_uf')->value(),
            rtTitulo: $this->filled('rt_titulo') ? $this->string('rt_titulo')->value() : null,
            rtEmail: ! $this->boolean('sou_rt') && $this->filled('rt_email') ? $this->string('rt_email')->value() : null,
            souRt: $this->boolean('sou_rt'), nome: $this->string('nome')->value(), email: $this->string('email')->value(), celular: $this->string('celular')->value(), senha: $this->string('senha')->value(),
        );
    }
}
