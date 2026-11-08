<?php

declare(strict_types=1);

namespace Vidra\Bancada\Domain;

use DateTimeImmutable;

final readonly class Resultado
{
    private function __construct(public string $id, public string $laboratorioId, public string $pedidoId, public string $itemId, public string $pacienteId, public string $analitoId, public string $nome, public ?string $unidade, public ?Decimal $valor, public ?string $texto, public int $digitadoPor, public string $digitadorNome, public DateTimeImmutable $digitadoEm, public ?Faixa $faixa, public Classificacao $classificacao) {}

    public static function digitar(string $id, string $laboratorioId, string $pedidoId, string $itemId, string $pacienteId, string $analitoId, string $nome, ?string $unidade, ?string $valor, ?string $texto, int $por, string $digitadorNome, DateTimeImmutable $quando, ?Faixa $faixa): self
    {
        $texto = $texto === null ? null : trim($texto);
        if (($valor === null) === ($texto === null || $texto === '')) {
            throw ErroDeBancada::com('Informe um valor numérico ou um texto para o resultado.');
        }
        $decimal = $valor === null ? null : new Decimal(str_replace(',', '.', trim($valor)));

        return new self($id, $laboratorioId, $pedidoId, $itemId, $pacienteId, $analitoId, $nome, $unidade, $decimal, $texto ?: null, $por, $digitadorNome, $quando, $faixa, $decimal === null ? Classificacao::Qualitativo : (new MotorDeReferencia)->classificar($decimal, $faixa));
    }

    public static function restaurar(string $id, string $laboratorioId, string $pedidoId, string $itemId, string $pacienteId, string $analitoId, string $nome, ?string $unidade, ?Decimal $valor, ?string $texto, int $por, string $digitadorNome, DateTimeImmutable $quando, ?Faixa $faixa, Classificacao $classificacao): self
    {
        return new self($id, $laboratorioId, $pedidoId, $itemId, $pacienteId, $analitoId, $nome, $unidade, $valor, $texto, $por, $digitadorNome, $quando, $faixa, $classificacao);
    }
}
