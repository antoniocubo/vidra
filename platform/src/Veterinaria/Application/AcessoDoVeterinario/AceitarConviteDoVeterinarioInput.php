<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\AcessoDoVeterinario;

final readonly class AceitarConviteDoVeterinarioInput
{
    public function __construct(public string $token, public string $senha) {}
}
