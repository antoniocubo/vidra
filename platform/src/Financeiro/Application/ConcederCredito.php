<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Application;

use DateTimeImmutable;
use DateTimeZone;
use Vidra\Financeiro\Domain\FaturaInvalida;
use Vidra\Financeiro\Domain\FaturaRepository;
use Vidra\Financeiro\Domain\NotaDeCredito;

final readonly class ConcederCredito
{
    public function __construct(private FaturaRepository $faturas) {}

    public function __invoke(ConcederCreditoInput $input): void
    {
        $fatura = $this->faturas->porId($input->faturaId) ?? throw FaturaInvalida::com('Fatura não encontrada.');
        $fatura->creditar(new NotaDeCredito($input->motivo, $input->valorCentavos, $input->por, new DateTimeImmutable('now', new DateTimeZone('UTC'))));
        $this->faturas->save($fatura);
    }
}
