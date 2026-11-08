<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Vinculo;

enum RecebeLaudo: string
{
    case CadaLaudo = 'cada_laudo';
    case ResumoDiario = 'resumo_diario';
}
