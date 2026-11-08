<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Animal;

enum Especie: string
{
    case Cao = 'cao';
    case Gato = 'gato';
    case Ave = 'ave';
    case Equino = 'equino';
    case Outra = 'outra';
}
