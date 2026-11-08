<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application\MontarLaudo;

use Vidra\Laudos\Domain\ConteudoDoLaudo;
use Vidra\Laudos\Domain\FonteDoLaudo;
use Vidra\Laudos\Domain\LaudoRepository;
use Vidra\Laudos\Domain\PedidoNaoEncontrado;

final readonly class MontarLaudo
{
    public function __construct(private LaudoRepository $laudos, private FonteDoLaudo $fonte) {}

    public function __invoke(string $pedidoId): ConteudoDoLaudo
    {
        return $this->fonte->montar($this->laudos->doPedido($pedidoId) ?? throw new PedidoNaoEncontrado);
    }
}
