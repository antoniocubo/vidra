<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Application\EnviarFatura;

use Vidra\Financeiro\Domain\EmissorDeFatura;
use Vidra\Financeiro\Domain\FaturaInvalida;
use Vidra\Financeiro\Domain\FaturaRepository;

/** Manda a fatura de novo sem mudar a situação. Numa fatura ainda aberta, é o primeiro envio. */
final readonly class ReenviarFatura
{
    public function __construct(private FaturaRepository $faturas, private EmissorDeFatura $emissor) {}

    public function __invoke(string $faturaId): void
    {
        $fatura = $this->faturas->porId($faturaId) ?? throw FaturaInvalida::com('Fatura não encontrada.');
        if ($fatura->pagador->email === null) {
            throw FaturaInvalida::com("Cadastre o e-mail de faturamento de {$fatura->pagador->nome} antes de enviar.");
        }

        $this->emissor->enfileirar($fatura);
    }
}
