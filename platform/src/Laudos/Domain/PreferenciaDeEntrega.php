<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

interface PreferenciaDeEntrega
{
    public function doSolicitante(string $solicitanteId): string;
}
