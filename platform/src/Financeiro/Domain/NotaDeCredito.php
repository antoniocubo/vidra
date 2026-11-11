<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Domain;

use DateTimeImmutable;
use DateTimeZone;

/** Desconto concedido depois de a fatura ser enviada. É o único ajuste possível. */
final readonly class NotaDeCredito
{
    public string $motivo;

    public DateTimeImmutable $em;

    public function __construct(string $motivo, public int $valorCentavos, public int $por, DateTimeImmutable $em)
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw FaturaInvalida::com('Diga o motivo do crédito.');
        }
        if ($valorCentavos <= 0) {
            throw FaturaInvalida::com('O crédito precisa ser maior que zero.');
        }

        $this->motivo = $motivo;
        $this->em = $em->setTimezone(new DateTimeZone('UTC'));
    }
}
