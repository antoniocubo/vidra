<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Animal;

enum Sexo: string
{
    case Femea = 'femea';
    case Macho = 'macho';
}
