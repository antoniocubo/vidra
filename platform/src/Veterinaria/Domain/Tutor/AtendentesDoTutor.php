<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Tutor;

interface AtendentesDoTutor
{
    public function nome(int $id): ?string;
}
