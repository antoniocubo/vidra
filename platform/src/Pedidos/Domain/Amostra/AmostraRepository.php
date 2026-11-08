<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Amostra;

use DateTimeImmutable;
use Vidra\Pedidos\Domain\Pedido\PedidoId;

interface AmostraRepository
{
    public function nextIdentity(): AmostraId;

    /** Grava a amostra e os itens do pedido que a usam. */
    public function save(Amostra $amostra): void;

    /** Só enxerga o laboratório atual: a amostra de outro laboratório volta null. */
    public function porId(AmostraId $id): ?Amostra;

    public function porCodigo(CodigoDaAmostra $codigo): ?Amostra;

    /**
     * Todas as amostras do pedido, inclusive as rejeitadas, na ordem de criação.
     *
     * @return list<Amostra>
     */
    public function doPedido(PedidoId $pedidoId): array;

    /**
     * Amostras que chegaram no intervalo [de, ate), da mais recente para a mais antiga.
     *
     * @return list<Amostra>
     */
    public function recebidasEntre(DateTimeImmutable $de, DateTimeImmutable $ate): array;

    /**
     * Amostras que ainda não chegaram, das mais antigas para as mais novas.
     *
     * @return list<Amostra>
     */
    public function aguardandoChegada(int $limite): array;
}
