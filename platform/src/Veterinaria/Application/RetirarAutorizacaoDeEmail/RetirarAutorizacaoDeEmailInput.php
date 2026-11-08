<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\RetirarAutorizacaoDeEmail;

final readonly class RetirarAutorizacaoDeEmailInput
{
    public function __construct(public string $tutorId, public int $atendenteId) {}
}
