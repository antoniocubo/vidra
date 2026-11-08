<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Amostra;

use Vidra\Pedidos\Domain\Pedido\ItemDoPedido;
use Vidra\Pedidos\Domain\Pedido\Pedido;

/** Regra de etiquetas: um tubo por material distinto, não um por exame. */
final class AmostrasDoPedido
{
    /** @return list<Amostra> na ordem em que cada material aparece no pedido */
    public static function gerar(Pedido $pedido, AmostraRepository $amostras): array
    {
        $porMaterial = [];
        foreach ($pedido->itens() as $item) {
            if (! $item->cancelado()) {
                $porMaterial[self::chave($item)][] = $item;
            }
        }

        $geradas = [];
        $sequencia = 0;
        foreach ($porMaterial as $itens) {
            $geradas[] = Amostra::gerar(
                $amostras->nextIdentity(),
                $pedido->laboratorioId,
                $pedido->id,
                CodigoDaAmostra::de($pedido->protocolo, ++$sequencia),
                $itens[0]->material,
                array_map(fn (ItemDoPedido $item): string => $item->id->value, $itens),
            );
        }

        return $geradas;
    }

    private static function chave(ItemDoPedido $item): string
    {
        return mb_strtolower(trim($item->material));
    }
}
