<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\PedirRecoleta;

use Vidra\Pedidos\Domain\Amostra\Amostra;
use Vidra\Pedidos\Domain\Amostra\AmostraId;
use Vidra\Pedidos\Domain\Amostra\AmostraRepository;
use Vidra\Pedidos\Domain\Amostra\CodigoDaAmostra;
use Vidra\Pedidos\Domain\Amostra\Exceptions\AmostraNaoEncontrada;
use Vidra\Pedidos\Domain\Pedido\Exceptions\PedidoNaoEncontrado;
use Vidra\Pedidos\Domain\Pedido\PedidoRepository;
use Vidra\Shared\Domain\Transacao;

final readonly class PedirRecoleta
{
    public function __construct(private AmostraRepository $amostras, private PedidoRepository $pedidos, private Transacao $transacao) {}

    /** @return string o código da nova amostra, ex.: A-0412-3 */
    public function __invoke(string $amostraId): string
    {
        return $this->transacao->executar(function () use ($amostraId): string {
            $amostra = $this->amostras->porId(new AmostraId($amostraId)) ?? throw AmostraNaoEncontrada::para();
            $pedido = $this->pedidos->porId($amostra->pedidoId) ?? throw PedidoNaoEncontrado::para();

            $ultima = max([0, ...array_map(fn (Amostra $a): int => $a->codigo->sequencia(), $this->amostras->doPedido($pedido->id))]);
            $nova = $amostra->pedirRecoleta($this->amostras->nextIdentity(), CodigoDaAmostra::de($pedido->protocolo, $ultima + 1));

            $this->amostras->save($amostra);
            $this->amostras->save($nova);

            return $nova->codigo->value;
        });
    }
}
