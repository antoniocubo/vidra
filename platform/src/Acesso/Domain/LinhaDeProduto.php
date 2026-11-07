<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

enum LinhaDeProduto: string
{
    case Pet = 'pet';
    case Clinica = 'clinica';
}
