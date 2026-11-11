<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application;

use DateTimeImmutable;
use Vidra\Laudos\Domain\Conferencia;
use Vidra\Laudos\Domain\LaudoRepository;
use Vidra\Laudos\Domain\PedidoNaoEncontrado;
use Vidra\Laudos\Domain\ResponsavelTecnico;
use Vidra\Laudos\Domain\Retificacao;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Shared\Domain\Transacao;

final readonly class ReabrirLaudo
{
    public function __construct(private LaudoRepository $laudos, private Conferencia $conferencia, private ResponsavelTecnico $rt, private Transacao $transacao, private LaboratorioAtual $laboratorio) {}

    public function __invoke(ReabrirLaudoInput $input): void
    {
        $this->transacao->executar(function () use ($input): void {
            $this->conferencia->bloquear($input->pedidoId);
            $l = $this->laudos->doPedido($input->pedidoId) ?? throw new PedidoNaoEncontrado;
            if ($l->laboratorioId !== $this->laboratorio->id()) {
                throw new PedidoNaoEncontrado;
            }
            $l = $l->reabrir(new Retificacao($input->motivo, $input->por, new DateTimeImmutable), $this->rt->ehRt($input->por));
            $this->laudos->save($l);
            $this->conferencia->reabrirItens($input->pedidoId);
        });
    }
}
