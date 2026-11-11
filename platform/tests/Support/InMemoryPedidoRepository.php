<?php

declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Vidra\Pedidos\Domain\Pedido\ItemId;
use Vidra\Pedidos\Domain\Pedido\Pedido;
use Vidra\Pedidos\Domain\Pedido\PedidoId;
use Vidra\Pedidos\Domain\Pedido\PedidoRepository;
use Vidra\Pedidos\Domain\Pedido\Protocolo;

final class InMemoryPedidoRepository implements PedidoRepository
{
    /** @var array<string, Pedido> */
    public array $items = [];

    private int $protocolo = 0;

    public function nextIdentity(): PedidoId
    {
        return new PedidoId((string) Str::uuid7());
    }

    public function nextItemIdentity(): ItemId
    {
        return new ItemId((string) Str::uuid7());
    }

    public function proximoProtocolo(): Protocolo
    {
        return new Protocolo(++$this->protocolo);
    }

    public function save(Pedido $pedido): void
    {
        $this->items[$pedido->id->value] = $pedido;
    }

    public function porId(PedidoId $id): ?Pedido
    {
        return $this->items[$id->value] ?? null;
    }

    public function porProtocolo(Protocolo $protocolo): ?Pedido
    {
        foreach ($this->items as $pedido) {
            if ($pedido->protocolo->equals($protocolo)) {
                return $pedido;
            }
        }

        return null;
    }

    /** @var array<string, true> */
    public array $comLaudo = [];

    public function temLaudo(PedidoId $id): bool
    {
        return isset($this->comLaudo[$id->value]);
    }

    public function porIds(array $ids): array
    {
        return array_intersect_key($this->items, array_flip($ids));
    }

    public function criadosEntre(DateTimeImmutable $de, DateTimeImmutable $ate, int $limite): array
    {
        return array_slice(array_values(array_filter($this->items, fn (Pedido $p): bool => $p->criadoEm >= $de && $p->criadoEm < $ate)), 0, $limite);
    }
}
