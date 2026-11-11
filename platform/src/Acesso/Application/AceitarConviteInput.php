<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application;

use SensitiveParameter;

final readonly class AceitarConviteInput
{
    public function __construct(public string $token, public string $nome, #[SensitiveParameter] public string $senha, public ?int $usuarioId, public ?string $emailAutenticado) {}
}
