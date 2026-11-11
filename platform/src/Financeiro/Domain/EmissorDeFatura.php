<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Domain;

interface EmissorDeFatura
{
    /** Manda a fatura, com o PDF anexo, ao e-mail do pagador. */
    public function enviar(Fatura $fatura, string $pdf): void;

    /** Coloca o envio na fila: o e-mail e o PDF saem fora da requisição. */
    public function enfileirar(Fatura $fatura): void;
}
