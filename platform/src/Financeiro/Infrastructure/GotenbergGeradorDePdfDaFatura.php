<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Infrastructure;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Vidra\Financeiro\Domain\Fatura;
use Vidra\Financeiro\Domain\GeradorDePdfDaFatura;

final readonly class GotenbergGeradorDePdfDaFatura implements GeradorDePdfDaFatura
{
    public function gerar(Fatura $fatura): string
    {
        try {
            $resposta = Http::timeout(30)->attach('files', $this->html($fatura), 'index.html')->post(rtrim(config()->string('services.gotenberg.url'), '/').'/forms/chromium/convert/html', [
                'paperWidth' => '8.27', 'paperHeight' => '11.69', 'marginTop' => '0.4', 'marginBottom' => '0.4', 'marginLeft' => '0.4', 'marginRight' => '0.4', 'printBackground' => 'true',
            ]);
        } catch (ConnectionException $e) {
            throw new FalhaAoGerarPdfDaFatura('Não foi possível conectar ao Gotenberg para gerar a fatura.', previous: $e);
        }
        if (! $resposta->successful() || ! str_starts_with($resposta->body(), '%PDF-')) {
            throw new FalhaAoGerarPdfDaFatura('O Gotenberg não gerou um PDF válido (HTTP '.$resposta->status().').');
        }

        return $resposta->body();
    }

    private function html(Fatura $fatura): string
    {
        $laboratorio = DB::connection(config()->string('vidra.conexao_sistema'))->table('laboratorios')->where('id', $fatura->laboratorioId)->first(['nome', 'cnpj', 'telefone', 'endereco', 'cidade', 'uf']);

        return view('financeiro.fatura', ['fatura' => $fatura, 'laboratorio' => $laboratorio])->render();
    }
}
