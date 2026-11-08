<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Vidra\Catalogo\Application\DefinirFaixasInput;

final class DefinirExameReferenciasRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $faixas = $this->input('faixas');
        if (! is_array($faixas)) {
            return;
        }
        foreach ($faixas as &$f) {
            if (! is_array($f)) {
                continue;
            } foreach (['minimo', 'maximo', 'criticoBaixo', 'criticoAlto'] as $campo) {
                if (isset($f[$campo]) && is_string($f[$campo])) {
                    $f[$campo] = trim($f[$campo]) === '' ? null : str_replace(',', '.', trim($f[$campo]));
                }
            }
        } unset($f);
        $this->merge(['faixas' => $faixas]);
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        $rules = ['analitoId' => ['required', 'uuid'], 'faixas' => ['present', 'array', 'max:500'], 'faixas.*.perfil' => ['required', 'string', 'max:255', 'distinct']];
        foreach (['minimo', 'maximo', 'criticoBaixo', 'criticoAlto'] as $campo) {
            $rules['faixas.*.'.$campo] = ['nullable', 'string', 'regex:/^-?\d{1,10}(?:\.\d{1,4})?$/D'];
        }

        return $rules;
    }

    public function toInput(): DefinirFaixasInput
    {
        /** @var list<array{perfil:string,minimo?:?string,maximo?:?string,criticoBaixo?:?string,criticoAlto?:?string}> $linhas */
        $linhas = $this->validated('faixas');

        return new DefinirFaixasInput($this->string('analitoId')->value(), array_map(fn (array $f): array => ['perfil' => $f['perfil'], 'minimo' => $f['minimo'] ?? null, 'maximo' => $f['maximo'] ?? null, 'criticoBaixo' => $f['criticoBaixo'] ?? null, 'criticoAlto' => $f['criticoAlto'] ?? null], $linhas));
    }
}
