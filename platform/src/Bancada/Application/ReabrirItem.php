<?php

declare(strict_types=1);

namespace Vidra\Bancada\Application;

use Vidra\Bancada\Domain\ErroDeBancada;
use Vidra\Bancada\Domain\Trabalhos;
use Vidra\Shared\Domain\Transacao;

final readonly class ReabrirItem
{
    public function __construct(private ObterTrabalho $obter, private Trabalhos $trabalhos, private Transacao $transacao) {}

    public function __invoke(string $id): void
    {
        $this->transacao->executar(function () use ($id): void {
            $i = ($this->obter)($id);
            $this->trabalhos->bloquear($i->pedidoId);
            $i = ($this->obter)($id);
            if ($i->situacao !== 'resultado_digitado') {
                throw ErroDeBancada::com('Só resultados aguardando conferência podem ser reabertos.');
            }
            $this->trabalhos->transicionar($i->pedidoId, $id, 'em_analise');
        });
    }
}
