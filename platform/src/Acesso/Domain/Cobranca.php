<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

enum Cobranca: string
{
    case Mensal = 'mensal';
    case Anual = 'anual';
}
