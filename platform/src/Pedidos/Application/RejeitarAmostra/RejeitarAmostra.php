<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\RejeitarAmostra;

use Vidra\Pedidos\Domain\Amostra\AmostraId;
use Vidra\Pedidos\Domain\Amostra\AmostraRepository;
use Vidra\Pedidos\Domain\Amostra\Exceptions\AmostraNaoEncontrada;
use Vidra\Pedidos\Domain\Amostra\MotivoDeRejeicao;
use Vidra\Pedidos\Domain\Pedido\PedidoRepository;
use Vidra\Shared\Domain\Transacao;

final readonly class RejeitarAmostra
{
    public function __construct(private AmostraRepository $amostras, private PedidoRepository $pedidos, private Transacao $transacao) {}

    public function __invoke(RejeitarAmostraInput $input): void
    {
        $this->transacao->executar(function () use ($input): void {
            $amostra = $this->amostras->porId(new AmostraId($input->amostraId)) ?? throw AmostraNaoEncontrada::para();
            $amostra->rejeitar(MotivoDeRejeicao::from($input->motivo), $input->texto);
            $this->amostras->save($amostra);

            // Sem tubo aceito, os exames voltam a esperar uma nova coleta.
            $pedido = $this->pedidos->porId($amostra->pedidoId);
            if ($pedido !== null) {
                $pedido->aguardarNovaAmostraDe($amostra->itemIds);
                $this->pedidos->save($pedido);
            }
        });
    }
}
