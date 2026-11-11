<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\PedidosDoVeterinario;

final readonly class PedidosDoVeterinarioInput
{
    public function __construct(public string $busca = '', public int $pagina = 1) {}
}
