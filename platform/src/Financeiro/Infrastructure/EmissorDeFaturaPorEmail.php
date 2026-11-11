<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Infrastructure;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Vidra\Financeiro\Domain\EmissorDeFatura;
use Vidra\Financeiro\Domain\Fatura;
use Vidra\Financeiro\Domain\FaturaInvalida;
use Vidra\Financeiro\Infrastructure\Jobs\EnviarFaturaPorEmail;
use Vidra\Financeiro\Infrastructure\Mail\FaturaMail;

final readonly class EmissorDeFaturaPorEmail implements EmissorDeFatura
{
    public function enviar(Fatura $fatura, string $pdf): void
    {
        $email = $fatura->pagador->email ?? throw FaturaInvalida::com("Cadastre o e-mail de faturamento de {$fatura->pagador->nome} antes de enviar.");
        $laboratorio = (string) DB::connection(config()->string('vidra.conexao_sistema'))->table('laboratorios')->where('id', $fatura->laboratorioId)->value('nome');

        Mail::to($email)->send(new FaturaMail($fatura, $laboratorio, $pdf));
    }

    public function enfileirar(Fatura $fatura): void
    {
        EnviarFaturaPorEmail::dispatch($fatura->laboratorioId, $fatura->id);
    }
}
