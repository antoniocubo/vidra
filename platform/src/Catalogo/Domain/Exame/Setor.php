<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Domain\Exame;

enum Setor: string
{
    case Hematologia = 'hematologia';
    case Bioquimica = 'bioquimica';
    case Urinalise = 'urinalise';
    case Parasitologia = 'parasitologia';
    case Hormonios = 'hormonios';
    case Microbiologia = 'microbiologia';
    case Outro = 'outro';
}
