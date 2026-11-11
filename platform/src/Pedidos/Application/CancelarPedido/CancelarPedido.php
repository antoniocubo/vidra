<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\CancelarPedido;

use Vidra\Pedidos\Domain\Amostra\AmostraRepository;
use Vidra\Pedidos\Domain\Amostra\AmostrasDoPedido;
use Vidra\Pedidos\Domain\Pedido\Cancelamento;
use Vidra\Pedidos\Domain\Pedido\Exceptions\PedidoNaoEncontrado;
use Vidra\Pedidos\Domain\Pedido\PedidoId;
use Vidra\Pedidos\Domain\Pedido\PedidoRepository;
use Vidra\Pedidos\Domain\Relogio;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Shared\Domain\Transacao;

final readonly class CancelarPedido
{
    public function __construct(private PedidoRepository $pedidos, private AmostraRepository $amostras,
        private LaboratorioAtual $laboratorio, private Transacao $transacao, private Relogio $relogio) {}

    public function __invoke(CancelarPedidoInput $input): void
    {
        $laboratorioId = $this->laboratorio->id();
        $this->transacao->executar(function () use ($input, $laboratorioId): void {
            $pedido = $this->pedidos->porId(new PedidoId($input->pedido)) ?? throw PedidoNaoEncontrado::para();
            if ($this->pedidos->temLaudo($pedido->id)) {
                throw \Vidra\Pedidos\Domain\Pedido\Exceptions\PedidoInvalido::jaLiberado();
            }
            if ($pedido->laboratorioId !== $laboratorioId) {
                throw PedidoNaoEncontrado::para();
            }
            $cancelamento = new Cancelamento($input->motivo, $input->por, $this->relogio->agora());
            $pedido->cancelar($cancelamento);
            $amostras = $this->amostras->doPedido($pedido->id);
            AmostrasDoPedido::cancelar($amostras);
            $this->pedidos->save($pedido);
            foreach ($amostras as $amostra) {
                $this->amostras->save($amostra);
            }
        });
    }
}
