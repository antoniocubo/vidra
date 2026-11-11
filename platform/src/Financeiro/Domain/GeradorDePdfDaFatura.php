<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Domain;

interface GeradorDePdfDaFatura
{
    /** Os bytes do PDF da fatura como ela está agora (com os créditos já concedidos). */
    public function gerar(Fatura $fatura): string;
}
