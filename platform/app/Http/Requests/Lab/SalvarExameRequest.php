<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Vidra\Catalogo\Application\DadosExameInput;
use Vidra\Catalogo\Domain\Exame\Setor;

final class SalvarExameRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['codigo' => strtoupper($this->string('codigo')->trim()->value())]);
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'regex:/^[A-Z0-9-]{2,12}$/D'], 'nome' => ['required', 'string', 'max:255'], 'setor' => ['required', Rule::enum(Setor::class)], 'material' => ['required', 'string', 'max:255'], 'metodo' => ['nullable', 'string', 'max:255'], 'prazo_horas' => ['required', 'integer', 'between:1,720'], 'preco_centavos' => ['required', 'integer', 'min:0', 'max:999999999999'], 'analitos' => ['required', 'array', 'min:1', 'max:100'], 'analitos.*.id' => ['nullable', 'uuid', 'distinct'], 'analitos.*.nome' => ['required', 'string', 'max:255', 'distinct:ignore_case'], 'analitos.*.unidade' => ['nullable', 'string', 'max:255'], 'analitos.*.casas_decimais' => ['required', 'integer', 'between:0,4'],
        ];
    }

    public function toInput(): DadosExameInput
    {
        /** @var list<array{id?:?string,nome:string,unidade?:?string,casas_decimais:int|string}> $linhas */
        $linhas = $this->validated('analitos');
        $analitos = array_map(fn (array $a): array => ['id' => $a['id'] ?? null, 'nome' => $a['nome'], 'unidade' => $a['unidade'] ?? null, 'casas_decimais' => (int) $a['casas_decimais']], $linhas);

        return new DadosExameInput($this->string('codigo')->value(), $this->string('nome')->value(), $this->string('setor')->value(), $this->string('material')->value(), $this->filled('metodo') ? $this->string('metodo')->value() : null, $this->integer('prazo_horas'), $this->integer('preco_centavos'), $analitos);
    }
}
