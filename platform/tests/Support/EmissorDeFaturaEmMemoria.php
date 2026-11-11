<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;
use Vidra\Financeiro\Domain\EmissorDeFatura;
use Vidra\Financeiro\Domain\Fatura;

/** Registra o que seria enviado e enfileirado; pode falhar no envio. */
final class EmissorDeFaturaEmMemoria implements EmissorDeFatura
{
    /** @var list<string> */
    public array $enviadas = [];

    /** @var list<string> */
    public array $enfileiradas = [];

    public bool $falhar = false;

    public function enviar(Fatura $fatura, string $pdf): void
    {
        if ($this->falhar) {
            throw new RuntimeException('SES fora do ar.');
        }
        $this->enviadas[] = $fatura->id;
    }

    public function enfileirar(Fatura $fatura): void
    {
        $this->enfileiradas[] = $fatura->id;
    }
}
