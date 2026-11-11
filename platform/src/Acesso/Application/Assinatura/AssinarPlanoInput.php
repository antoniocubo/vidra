<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application\Assinatura;

final readonly class AssinarPlanoInput
{
    public function __construct(public string $plano, public string $cobranca) {}
}
