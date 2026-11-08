<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application;

use DateTimeImmutable;
use DateTimeZone;
use Vidra\Laudos\Domain\AvisoDeValorCritico;
use Vidra\Laudos\Domain\Conferencia;
use Vidra\Laudos\Domain\ErroDeLiberacao;
use Vidra\Laudos\Domain\LaudoRepository;
use Vidra\Laudos\Domain\ResponsavelTecnico;
use Vidra\Shared\Domain\Transacao;

final readonly class RegistrarAvisoDeValorCritico
{
    public function __construct(private ObterConferencia $obter, private Conferencia $conferencia, private LaudoRepository $laudos, private ResponsavelTecnico $rt, private Transacao $transacao) {}

    public function __invoke(RegistrarAvisoInput $input): void
    {
        $this->transacao->executar(function () use ($input): void {
            $this->conferencia->bloquear($input->pedidoId);
            $p = ($this->obter)($input->pedidoId);
            if (! $this->rt->ehRt($input->por)) {
                throw ErroDeLiberacao::com('Só o responsável técnico registra o aviso do valor crítico.');
            }
            if (! $p->pronto() || $this->laudos->doPedido($p->id) !== null) {
                throw ErroDeLiberacao::com('O pedido precisa estar aguardando liberação.');
            }
            foreach ($p->resultados as $r) {
                if ($r->id === $input->resultadoId && $r->critico()) {
                    $this->laudos->saveAviso(new AvisoDeValorCritico($this->laudos->nextIdentity(), $p->laboratorioId, $p->id, $r->id, $r->valor, $r->digitadoEm, $input->por, new DateTimeImmutable('now', new DateTimeZone('UTC')), trim($input->destinatario), $p->telefoneCritico));

                    return;
                }
            }
            throw ErroDeLiberacao::com('Escolha um resultado crítico deste pedido.');
        });
    }
}
