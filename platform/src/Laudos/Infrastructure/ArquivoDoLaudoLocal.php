<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Vidra\Laudos\Domain\ArquivoDoLaudo;

final readonly class ArquivoDoLaudoLocal implements ArquivoDoLaudo
{
    public function guardar(string $laboratorioId, string $laudoId, string $bytes): string
    {
        $caminho = $laboratorioId.'/'.$laudoId.'.pdf';
        Storage::disk('laudos')->put($caminho, $bytes);

        return $caminho;
    }

    public function ler(string $caminho): string
    {
        return Storage::disk('laudos')->get($caminho) ?? throw new RuntimeException('O arquivo PDF do laudo não foi encontrado.');
    }
}
