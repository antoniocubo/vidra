<?php

declare(strict_types=1);

namespace Tests\Support;

use Vidra\Bancada\Domain\Resultado;
use Vidra\Bancada\Domain\ResultadoAnterior;
use Vidra\Bancada\Domain\ResultadoRepository;

final class InMemoryResultadoBancadaRepository implements ResultadoRepository
{
    /** @var array<string,Resultado> */
    public array $resultados = [];

    /** @var array<string,ResultadoAnterior> */
    public array $anteriores = [];

    public function nextIdentity(): string
    {
        return 'resultado-'.(count($this->resultados) + 1);
    }

    public function save(Resultado $resultado): void
    {
        $this->resultados[$resultado->id] = $resultado;
    }

    public function doItem(string $itemId): array
    {
        return array_values(array_filter($this->resultados, fn (Resultado $r): bool => $r->itemId === $itemId));
    }

    public function doPedido(string $pedidoId): array
    {
        return array_values(array_filter($this->resultados, fn (Resultado $r): bool => $r->pedidoId === $pedidoId));
    }

    public function anteriorLiberado(string $pacienteId, string $analitoId, string $pedidoAtual): ?ResultadoAnterior
    {
        return $this->anteriores[$pacienteId.'-'.$analitoId] ?? null;
    }
}
