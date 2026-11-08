<?php

declare(strict_types=1);

namespace Vidra\Bancada\Infrastructure;

use Vidra\Bancada\Domain\Trabalho;
use Vidra\Bancada\Domain\Trabalhos;
use Vidra\Pedidos\Application\ParaBancada\ConsultarTrabalho;
use Vidra\Pedidos\Application\ParaBancada\TransicionarItem;
use Vidra\Pedidos\Infrastructure\Persistence\PedidoModel;

final readonly class TrabalhosDosPedidos implements Trabalhos
{
    public function __construct(private ConsultarTrabalho $consultar, private TransicionarItem $transicionar) {}

    public function todos(): array
    {
        return array_map(fn ($i): Trabalho => new Trabalho($i->id, $i->pedidoId, $i->laboratorioId, $i->exameId, $i->exame, $i->setor, $i->situacao, $i->pacienteId, $i->paciente, $i->descricao, $i->especieCurta, $i->perfil, $i->protocolo, $i->urgente, $i->amostra, $i->chegada?->format('c'), $i->solicitante, $i->telefoneCritico), ($this->consultar)());
    }

    public function transicionar(string $pedidoId, string $itemId, string $situacao): void
    {
        ($this->transicionar)($pedidoId, $itemId, $situacao);
    }

    public function bloquear(string $pedidoId): void
    {
        if (PedidoModel::query()->whereKey($pedidoId)->lockForUpdate()->first() === null) {
            throw new \Vidra\Bancada\Domain\ItemNaoEncontrado;
        }
    }
}
