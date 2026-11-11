<?php

declare(strict_types=1);

namespace Tests\Support;

use Vidra\Financeiro\Domain\Fatura;
use Vidra\Financeiro\Domain\GeradorDePdfDaFatura;

final class GeradorDePdfDaFaturaFalso implements GeradorDePdfDaFatura
{
    public function gerar(Fatura $fatura): string
    {
        return '%PDF-1.7 '.$fatura->numeroFormatado();
    }
}
