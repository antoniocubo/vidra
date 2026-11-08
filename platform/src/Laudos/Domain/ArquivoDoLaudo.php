<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

interface ArquivoDoLaudo
{
    public function guardar(string $laboratorioId, string $laudoId, string $bytes): string;

    public function ler(string $caminho): string;
}
