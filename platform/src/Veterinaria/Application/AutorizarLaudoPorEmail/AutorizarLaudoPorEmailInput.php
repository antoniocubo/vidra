<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\AutorizarLaudoPorEmail;

final readonly class AutorizarLaudoPorEmailInput
{
    public function __construct(public string $tutorId, public int $atendenteId) {}
}
