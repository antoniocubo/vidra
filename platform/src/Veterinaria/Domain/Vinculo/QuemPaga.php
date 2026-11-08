<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Vinculo;

enum QuemPaga: string
{
    case Clinica = 'clinica';
    case Veterinario = 'veterinario';
    case Tutor = 'tutor';
}
