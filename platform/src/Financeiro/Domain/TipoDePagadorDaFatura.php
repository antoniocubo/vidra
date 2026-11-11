<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Domain;

/** Quem recebe fatura mensal. O tutor paga no balcão e não entra aqui. */
enum TipoDePagadorDaFatura: string
{
    case Clinica = 'clinica';
    case Solicitante = 'solicitante';
}
