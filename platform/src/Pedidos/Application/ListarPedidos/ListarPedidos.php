<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\ListarPedidos;

use Vidra\Pedidos\Application\DiaLocal;
use Vidra\Pedidos\Application\Formato;
use Vidra\Pedidos\Application\Texto;
use Vidra\Pedidos\Domain\Pedido\ItemDoPedido;
use Vidra\Pedidos\Domain\Pedido\Pedido;
use Vidra\Pedidos\Domain\Pedido\PedidoRepository;
use Vidra\Pedidos\Domain\Pedido\SituacaoDoPedido;
use Vidra\Pedidos\Domain\Portas\Pacientes;
use Vidra\Pedidos\Domain\Portas\Solicitantes;
use Vidra\Pedidos\Domain\Relogio;

final readonly class ListarPedidos
{
    private const int LIMITE = 500;

    public function __construct(private PedidoRepository $pedidos, private Pacientes $pacientes, private Solicitantes $solicitantes, private Relogio $relogio) {}

    public function __invoke(ListarPedidosInput $input = new ListarPedidosInput): ListaDePedidosDto
    {
        $dia = DiaLocal::de($input->data, $this->relogio->agora());
        $situacao = $input->situacao === null ? null : SituacaoDoPedido::tryFrom($input->situacao);
        $pedidos = $this->pedidos->criadosEntre($dia->inicio, $dia->fim, self::LIMITE);
        if ($situacao !== null) {
            $pedidos = array_values(array_filter($pedidos, fn (Pedido $p): bool => $p->situacao() === $situacao));
        }

        $pacientes = $this->pacientes->resumos(array_values(array_unique(array_map(fn (Pedido $p): string => $p->pacienteId, $pedidos))));
        $solicitantes = $this->solicitantes->resumos(array_values(array_unique(array_map(fn (Pedido $p): string => $p->solicitanteId, $pedidos))));

        $linhas = [];
        foreach ($pedidos as $pedido) {
            $paciente = $pacientes[$pedido->pacienteId] ?? null;
            $solicitante = $solicitantes[$pedido->solicitanteId] ?? null;
            $busca = [(string) $pedido->protocolo, (string) $pedido->protocolo->numero, $paciente->nome ?? '', $paciente->responsavelNome ?? ''];
            if (! Texto::casa($input->busca, $busca)) {
                continue;
            }
            $estado = $pedido->situacao();
            $linhas[] = new PedidoResumoDto(
                $pedido->id->value,
                (string) $pedido->protocolo,
                $paciente->nome ?? 'Paciente removido',
                $paciente->especieCurta ?? '',
                $solicitante->nome ?? 'Solicitante removido',
                array_map(fn (ItemDoPedido $i): string => $i->codigo, $pedido->itens()),
                $estado->value,
                $estado->rotulo(),
                $pedido->urgente(),
                Formato::hora($pedido->criadoEm),
            );
        }

        return new ListaDePedidosDto($dia->data, $linhas);
    }
}
