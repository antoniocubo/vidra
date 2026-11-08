<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Vidra\Laudos\Domain\GeradorDePdf;

final readonly class GotenbergGeradorDePdf implements GeradorDePdf
{
    public function deHtml(string $html): string
    {
        try {
            $resposta = Http::timeout(30)->attach('files', $html, 'index.html')->post(rtrim(config()->string('services.gotenberg.url'), '/').'/forms/chromium/convert/html', [
                'paperWidth' => '8.27', 'paperHeight' => '11.69', 'marginTop' => '0.4', 'marginBottom' => '0.4', 'marginLeft' => '0.4', 'marginRight' => '0.4', 'printBackground' => 'true',
            ]);
        } catch (ConnectionException $e) {
            throw new FalhaAoGerarPdf('Não foi possível conectar ao Gotenberg para gerar o laudo.', previous: $e);
        }
        if (! $resposta->successful() || ! str_starts_with($resposta->body(), '%PDF-')) {
            throw new FalhaAoGerarPdf('O Gotenberg não gerou um PDF válido (HTTP '.$resposta->status().').');
        }

        return $resposta->body();
    }
}
