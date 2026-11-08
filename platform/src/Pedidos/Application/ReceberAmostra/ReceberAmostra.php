<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\ReceberAmostra;

use Vidra\Pedidos\Domain\Amostra\AmostraRepository;
use Vidra\Pedidos\Domain\Amostra\CodigoDaAmostra;
use Vidra\Pedidos\Domain\Amostra\Exceptions\AmostraNaoEncontrada;
use Vidra\Pedidos\Domain\Pedido\PedidoRepository;
use Vidra\Pedidos\Domain\Portas\Pacientes;
use Vidra\Pedidos\Domain\Relogio;
use Vidra\Shared\Domain\Transacao;

final readonly class ReceberAmostra
{
    public function __construct(private AmostraRepository $amostras, private PedidoRepository $pedidos, private Pacientes $pacientes, private Transacao $transacao, private Relogio $relogio) {}

    /** @param string $codigoLido o que o leitor digitou, com ou sem maiúsculas */
    public function __invoke(string $codigoLido, int $por): AmostraRecebida
    {
        $digitado = strtoupper(trim($codigoLido));
        $codigo = CodigoDaAmostra::tentarLer($digitado) ?? throw AmostraNaoEncontrada::com($digitado);

        return $this->transacao->executar(function () use ($codigo, $digitado, $por): AmostraRecebida {
            $amostra = $this->amostras->porCodigo($codigo) ?? throw AmostraNaoEncontrada::com($digitado);
            $amostra->receber($this->relogio->agora(), $por);
            $this->amostras->save($amostra);

            $pedido = $this->pedidos->porId($amostra->pedidoId);
            if ($pedido !== null) {
                $pedido->iniciarAnaliseDe($amostra->itemIds);
                $this->pedidos->save($pedido);
            }
            $paciente = $pedido === null ? null : $this->pacientes->resumo($pedido->pacienteId);

            return new AmostraRecebida($amostra->id->value, $amostra->codigo->value, $paciente->nome ?? '', $amostra->material);
        });
    }
}
