<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Clinica;

enum FormaDePagamento: string
{
    case Pix = 'pix';
    case Boleto = 'boleto';
    case Transferencia = 'transferencia';
}
