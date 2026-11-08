<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Closure;
use DateTimeZone;
use Vidra\Veterinaria\Application\AdicionarAnimal\AdicionarAnimalInput;

/** @phpstan-import-type AnimalDados from AdicionarAnimalInput */
trait DadosAnimalRequest
{
    /** @return array<string, mixed> */
    private function regrasAnimal(string $prefixo): array
    {
        $regras = [
            'nome' => ['required', 'string', 'max:255'], 'especie' => ['required', 'in:cao,gato,ave,equino,outra'],
            'raca' => ['nullable', 'string', 'max:255'], 'sexo' => ['required', 'in:femea,macho'],
            'modo_idade' => ['required', 'in:data,aproximada'],
            'nascimento' => ['exclude_unless:'.$prefixo.'modo_idade,data', 'required', 'date_format:Y-m-d', 'before_or_equal:'.app(\Vidra\Veterinaria\Domain\Relogio::class)->agora()->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('Y-m-d')],
            'anos' => ['exclude_unless:'.$prefixo.'modo_idade,aproximada', 'required', 'integer', 'between:0,40'],
            'meses' => ['exclude_unless:'.$prefixo.'modo_idade,aproximada', 'required', 'integer', 'between:0,11', function (string $attribute, mixed $value, Closure $fail): void {
                $anos = substr($attribute, 0, -strlen('meses')).'anos';
                if ($this->integer($anos) === 40 && (int) $value > 0) {
                    $fail('A idade aproximada deve ser de até 40 anos.');
                }
            }],
            'peso' => ['nullable', 'numeric', 'gte:0.001', 'lt:2000'],
            'castrado' => ['required', 'in:sim,nao,nao_informado'],
            'microchip' => ['nullable', 'regex:/^\d{15}$/'], 'vinculo_veterinario_id' => ['nullable', 'uuid', function (string $attribute, mixed $value, Closure $fail): void {
                if (! isset(app(\Vidra\Veterinaria\Domain\Vinculo\VinculosParaEscolha::class)->nomes()[(string) $value])) {
                    $fail('Escolha um veterinário deste laboratório.');
                }
            }],
        ];
        $resultado = [];
        foreach ($regras as $campo => $regra) {
            $resultado[$prefixo.$campo] = $regra;
        }

        return $resultado;
    }

    /**
     * @param  array<string, mixed>  $animal
     * @return array<string, mixed>
     */
    private function normalizarAnimal(array $animal): array
    {
        if (isset($animal['peso']) && is_string($animal['peso'])) {
            $animal['peso'] = str_replace(',', '.', $animal['peso']);
        }

        // Campo numérico do navegador pode mandar "04": normaliza antes da regra integer.
        foreach (['anos', 'meses'] as $campo) {
            if (isset($animal[$campo]) && is_string($animal[$campo]) && preg_match('/^\d+$/', $animal[$campo]) === 1) {
                $animal[$campo] = (string) (int) $animal[$campo];
            }
        }

        return $animal;
    }

    /** @return AnimalDados */
    private function animalInput(string $prefixo): array
    {
        $estimado = $this->string($prefixo.'modo_idade')->value() === 'aproximada';
        $agora = app(\Vidra\Veterinaria\Domain\Relogio::class)->agora()->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        $nascimento = $this->string($prefixo.'nascimento')->value();
        if ($estimado) {
            // Parte do primeiro dia para evitar que 31 de março menos um mês vire março.
            $mes = $agora->modify('first day of this month')->modify('-'.($this->integer($prefixo.'anos') * 12 + $this->integer($prefixo.'meses')).' months');
            $dia = min((int) $agora->format('d'), (int) $mes->format('t'));
            $nascimento = $mes->setDate((int) $mes->format('Y'), (int) $mes->format('m'), $dia)->format('Y-m-d');
        }

        return [
            'nome' => $this->string($prefixo.'nome')->value(), 'especie' => $this->string($prefixo.'especie')->value(),
            'raca' => $this->filled($prefixo.'raca') ? $this->string($prefixo.'raca')->value() : null,
            'sexo' => $this->string($prefixo.'sexo')->value(), 'nascimento' => $nascimento, 'nascimentoEstimado' => $estimado,
            'pesoGramas' => $this->filled($prefixo.'peso') ? (int) round($this->float($prefixo.'peso') * 1000) : null,
            'castrado' => $this->string($prefixo.'castrado')->value(),
            'microchip' => $this->filled($prefixo.'microchip') ? $this->string($prefixo.'microchip')->value() : null,
            'vinculoVeterinarioId' => $this->filled($prefixo.'vinculo_veterinario_id') ? $this->string($prefixo.'vinculo_veterinario_id')->value() : null,
        ];
    }
}
