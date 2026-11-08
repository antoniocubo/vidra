<?php

declare(strict_types=1);

namespace Vidra\Bancada\Application;

use DateTimeImmutable;
use Vidra\Bancada\Domain\Resultado;

/** Contrato de leitura para outros módulos, sem expor o agregado interno. */
final readonly class ResultadoDaBancada
{
    public string $id;

    public string $itemId;

    public string $pacienteId;

    public string $analitoId;

    public string $nome;

    public ?string $unidade;

    public ?string $valor;

    public ?string $texto;

    public string $classificacao;

    public string $referencia;

    public string $digitadorNome;

    public DateTimeImmutable $digitadoEm;

    public function __construct(Resultado $r)
    {
        $this->id = $r->id;
        $this->itemId = $r->itemId;
        $this->pacienteId = $r->pacienteId;
        $this->analitoId = $r->analitoId;
        $this->nome = $r->nome;
        $this->unidade = $r->unidade;
        $this->valor = $r->valor?->value;
        $this->texto = $r->texto;
        $this->classificacao = $r->classificacao->value;
        $this->referencia = $r->faixa?->formatada() ?? 'Sem referência';
        $this->digitadorNome = $r->digitadorNome;
        $this->digitadoEm = $r->digitadoEm;
    }
}
