<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\AcessoDoVeterinario;

final readonly class ConvidarVeterinarioInput
{
    public function __construct(public string $vinculoId, public int $por) {}
}
