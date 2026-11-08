<?php

declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Vidra\Pedidos\Domain\Amostra\Amostra;
use Vidra\Pedidos\Domain\Amostra\AmostraId;
use Vidra\Pedidos\Domain\Amostra\AmostraRepository;
use Vidra\Pedidos\Domain\Amostra\CodigoDaAmostra;
use Vidra\Pedidos\Domain\Amostra\SituacaoDaAmostra;
use Vidra\Pedidos\Domain\Pedido\PedidoId;

final class InMemoryAmostraRepository implements AmostraRepository
{
    /** @var array<string, Amostra> */
    public array $items = [];

    public function nextIdentity(): AmostraId
    {
        return new AmostraId((string) Str::uuid7());
    }

    public function save(Amostra $amostra): void
    {
        $this->items[$amostra->id->value] = $amostra;
    }

    public function porId(AmostraId $id): ?Amostra
    {
        return $this->items[$id->value] ?? null;
    }

    public function porCodigo(CodigoDaAmostra $codigo): ?Amostra
    {
        foreach ($this->items as $amostra) {
            if ($amostra->codigo->equals($codigo)) {
                return $amostra;
            }
        }

        return null;
    }

    public function doPedido(PedidoId $pedidoId): array
    {
        return array_values(array_filter($this->items, fn (Amostra $a): bool => $a->pedidoId->equals($pedidoId)));
    }

    public function recebidasEntre(DateTimeImmutable $de, DateTimeImmutable $ate): array
    {
        return array_values(array_filter($this->items, fn (Amostra $a): bool => $a->recebidaEm() !== null && $a->recebidaEm() >= $de && $a->recebidaEm() < $ate));
    }

    public function aguardandoChegada(int $limite): array
    {
        return array_slice(array_values(array_filter($this->items, fn (Amostra $a): bool => $a->situacao() === SituacaoDaAmostra::Aguardando)), 0, $limite);
    }
}
