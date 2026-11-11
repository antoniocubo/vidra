<?php

declare(strict_types=1);

namespace Tests\Support;

use Vidra\Financeiro\Domain\ArquivosDeFatura;

final class ArquivosDeFaturaEmMemoria implements ArquivosDeFatura
{
    /** @var array<string, string> */
    public array $guardados = [];

    public function guardar(string $faturaId, string $bytes): void
    {
        $this->guardados[$faturaId] = $bytes;
    }
}
