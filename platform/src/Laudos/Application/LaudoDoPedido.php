<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application;

use Vidra\Laudos\Domain\LaudoRepository;

final readonly class LaudoDoPedido
{
    public function __construct(private LaudoRepository $laudos) {}

    public function __invoke(string $id): ?LaudoDto
    {
        $laudo = $this->laudos->doPedido($id);

        return $laudo === null ? null : new LaudoDto($laudo);
    }
}
