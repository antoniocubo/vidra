<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\ParaBancada;

use DateTimeImmutable;
use Vidra\Pedidos\Domain\Amostra\AmostraRepository;
use Vidra\Pedidos\Domain\Pedido\PedidoRepository;
use Vidra\Pedidos\Domain\Portas\Pacientes;
use Vidra\Pedidos\Domain\Portas\Solicitantes;

final readonly class ConsultarTrabalho
{
    public function __construct(private PedidoRepository $pedidos, private AmostraRepository $amostras, private Pacientes $pacientes, private Solicitantes $solicitantes) {}

    /**
     * @param list<string>|null $ids
     * @return list<ItemParaBancada> */
    public function __invoke(?array $ids = null): array
    {
        $pedidos = $ids === null ? $this->pedidos->criadosEntre(new DateTimeImmutable('1970-01-01'), new DateTimeImmutable('9999-01-01'), PHP_INT_MAX) : array_values($this->pedidos->porIds($ids));
        $itens = [];
        foreach ($pedidos as $p) {
            $paciente = $this->pacientes->resumo($p->pacienteId);
            $solicitante = $this->solicitantes->resumo($p->solicitanteId);
            $amostras = $this->amostras->doPedido($p->id);
            foreach ($p->itens() as $i) {
                $codigo = '';
                $chegada = null;
                foreach ($amostras as $a) {
                    if (in_array($i->id->value, $a->itemIds, true) && in_array($a->situacao()->value, ['recebida', 'aceita_com_ressalva'], true)) {
                        $codigo = $a->codigo->value;
                        $chegada = $a->recebidaEm();
                    }
                }
                $itens[] = new ItemParaBancada($i->id->value, $p->id->value, $p->laboratorioId, $i->exameId, $i->nome, $i->setor, $i->situacao->value, $p->pacienteId, $paciente->nome ?? 'Paciente removido', $paciente->descricao ?? '', $paciente->especieCurta ?? '', $paciente?->perfilDeReferencia, (string) $p->protocolo, $p->urgente(), $codigo, $chegada, $solicitante->nome ?? 'Solicitante removido', $solicitante->telefoneCritico ?? '');
            }
        }

        return $itens;
    }
}
