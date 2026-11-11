<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Domain;

interface ArquivosDeFatura
{
    /** Guarda o PDF enviado (o último substitui o anterior). */
    public function guardar(string $faturaId, string $bytes): void;
}
