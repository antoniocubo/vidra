<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\DetalharTutor;

final readonly class DetalharTutorInput
{
    public function __construct(public string $tutorId) {}
}
