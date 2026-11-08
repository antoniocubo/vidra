<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\ParaLaudo;

use Vidra\Pedidos\Domain\Amostra\AmostraRepository;
use Vidra\Pedidos\Domain\Pedido\Exceptions\PedidoNaoEncontrado;
use Vidra\Pedidos\Domain\Pedido\PedidoId;
use Vidra\Pedidos\Domain\Pedido\PedidoRepository;
use Vidra\Pedidos\Domain\Portas\Pacientes;
use Vidra\Pedidos\Domain\Portas\Solicitantes;
use Vidra\Pedidos\Domain\Portas\Vocabulario;

final readonly class LerPedidoParaLaudo
{
    public function __construct(private PedidoRepository $pedidos, private AmostraRepository $amostras, private Pacientes $pacientes, private Solicitantes $solicitantes, private Vocabulario $vocabulario) {}

    public function __invoke(string $id): PedidoParaLaudo
    {
        $p = $this->pedidos->porId(new PedidoId($id)) ?? throw PedidoNaoEncontrado::para();
        $paciente = $this->pacientes->resumo($p->pacienteId);
        $solicitante = $this->solicitantes->resumo($p->solicitanteId);
        $textos = $this->vocabulario->textos();
        $amostras = [];
        foreach ($this->amostras->doPedido($p->id) as $a) {
            if (in_array($a->situacao()->value, ['recebida', 'aceita_com_ressalva'], true)) {
                $amostras[] = ['recebidaEm' => $a->recebidaEm(), 'ressalva' => $a->ressalva()];
            }
        }
        $itens = [];
        foreach ($p->itens() as $i) {
            if (! $i->cancelado()) {
                $itens[] = ['id' => $i->id->value, 'exameId' => $i->exameId, 'nome' => $i->nome, 'material' => $i->material];
            }
        }

        return new PedidoParaLaudo(
            (string) $p->protocolo, $p->criadoEm, $p->solicitanteId,
            $paciente->nome ?? 'Paciente removido', $paciente->descricao ?? '', $paciente->responsavelNome ?? '',
            $paciente?->responsavelEmail, $paciente->autorizaEmail ?? false, $paciente->responsavelTelefone ?? '',
            $solicitante->nome ?? 'Solicitante removido', $solicitante->registro ?? '', $solicitante->email ?? '',
            ['paciente' => $textos->paciente, 'responsavel' => $textos->responsavel, 'solicitante' => $textos->solicitante], $itens, $amostras,
        );
    }
}
