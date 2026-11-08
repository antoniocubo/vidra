<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Pedido;

use DateTimeImmutable;

interface PedidoRepository
{
    public function nextIdentity(): PedidoId;

    public function nextItemIdentity(): ItemId;

    /**
     * Próximo número do laboratório atual. Seguro contra concorrência, mas só vale
     * dentro de uma transação: o bloqueio dura até ela terminar.
     */
    public function proximoProtocolo(): Protocolo;

    public function save(Pedido $pedido): void;

    /** Só enxerga o laboratório atual: o pedido de outro laboratório volta null. */
    public function porId(PedidoId $id): ?Pedido;

    public function porProtocolo(Protocolo $protocolo): ?Pedido;

    /**
     * @param  list<string>  $ids
     * @return array<string, Pedido> indexados pelo id
     */
    public function porIds(array $ids): array;

    /**
     * Pedidos criados no intervalo [de, ate), do mais novo para o mais antigo.
     *
     * @return list<Pedido>
     */
    public function criadosEntre(DateTimeImmutable $de, DateTimeImmutable $ate, int $limite): array;
}
