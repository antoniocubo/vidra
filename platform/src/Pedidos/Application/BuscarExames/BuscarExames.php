<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\BuscarExames;

use Vidra\Pedidos\Domain\Portas\CatalogoDeExames;
use Vidra\Pedidos\Domain\Portas\ExameDoPedido;

final readonly class BuscarExames
{
    public function __construct(private CatalogoDeExames $catalogo) {}

    /** @return list<ExameDoPedido> */
    public function __invoke(string $termo): array
    {
        return trim($termo) === '' ? [] : $this->catalogo->buscar($termo, 8);
    }
}
