<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application\ListarLaudos;

final readonly class ListarLaudosInput
{
    public function __construct(public string $busca = '', public ?string $de = null, public ?string $ate = null, public string $entrega = 'todas', public int $pagina = 1) {}
}
