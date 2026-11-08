<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

interface ResponsavelTecnico
{
    public function ehRt(int $usuarioId): bool;

    /**
     * @return array{nome:string,registro:string} */
    public function dados(): array;
}
