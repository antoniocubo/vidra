<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Animal;

enum Castrado: string
{
    case Sim = 'sim';
    case Nao = 'nao';
    case NaoInformado = 'nao_informado';
}
