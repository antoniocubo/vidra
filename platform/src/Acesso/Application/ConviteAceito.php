<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application;

final readonly class ConviteAceito
{
    public function __construct(public int $usuarioId, public string $laboratorioId) {}
}
