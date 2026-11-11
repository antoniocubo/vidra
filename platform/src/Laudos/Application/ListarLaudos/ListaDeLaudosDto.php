<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application\ListarLaudos;

final readonly class ListaDeLaudosDto
{
    /** @param list<LinhaDeLaudoDto> $laudos */
    public function __construct(public array $laudos, public int $pagina, public bool $temProxima) {}
}
