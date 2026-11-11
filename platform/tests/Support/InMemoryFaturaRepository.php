<?php

declare(strict_types=1);

namespace Tests\Support;

use Vidra\Financeiro\Domain\Competencia;
use Vidra\Financeiro\Domain\Fatura;
use Vidra\Financeiro\Domain\FaturaRepository;
use Vidra\Financeiro\Domain\FaturasEmAtraso;
use Vidra\Financeiro\Domain\PagadorDaFatura;

final class InMemoryFaturaRepository implements FaturaRepository, FaturasEmAtraso
{
    /** @var array<string, Fatura> */
    public array $items = [];

    private int $numero = 0;

    public function nextIdentity(): string
    {
        return 'fatura-'.(count($this->items) + 1);
    }

    public function proximoNumero(): int
    {
        return ++$this->numero;
    }

    public function save(Fatura $fatura): void
    {
        $this->items[$fatura->id] = $fatura;
    }

    public function porId(string $id): ?Fatura
    {
        // Cópia, como uma leitura do banco: mudar a fatura sem salvar não vale.
        return isset($this->items[$id]) ? clone $this->items[$id] : null;
    }

    public function doPagadorNaCompetencia(PagadorDaFatura $pagador, Competencia $competencia): ?Fatura
    {
        foreach ($this->items as $f) {
            if ($f->pagador->tipo === $pagador->tipo && $f->pagador->id === $pagador->id && $f->competencia->valor() === $competencia->valor()) {
                return $f;
            }
        }

        return null;
    }

    public function daCompetencia(Competencia $competencia): array
    {
        return array_values(array_filter($this->items, fn (Fatura $f): bool => $f->competencia->valor() === $competencia->valor()));
    }

    public function naoPagasAnterioresA(Competencia $competencia): array
    {
        return array_values(array_filter($this->items, fn (Fatura $f): bool => $f->competencia->valor() < $competencia->valor() && $f->pagaEm() === null));
    }
}
