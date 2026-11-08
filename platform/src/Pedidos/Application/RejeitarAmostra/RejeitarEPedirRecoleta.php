<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\RejeitarAmostra;

use Vidra\Pedidos\Application\PedirRecoleta\PedirRecoleta;
use Vidra\Shared\Domain\Transacao;

/** O botão "Rejeitar e pedir recoleta" da triagem: as duas coisas juntas ou nenhuma. */
final readonly class RejeitarEPedirRecoleta
{
    public function __construct(private RejeitarAmostra $rejeitar, private PedirRecoleta $pedirRecoleta, private Transacao $transacao) {}

    /** @return string o código da nova amostra */
    public function __invoke(RejeitarAmostraInput $input): string
    {
        return $this->transacao->executar(function () use ($input): string {
            ($this->rejeitar)($input);

            return ($this->pedirRecoleta)($input->amostraId);
        });
    }
}
