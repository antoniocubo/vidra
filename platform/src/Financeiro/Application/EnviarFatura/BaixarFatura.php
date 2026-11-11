<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Application\EnviarFatura;

use Vidra\Financeiro\Domain\FaturaInvalida;
use Vidra\Financeiro\Domain\FaturaRepository;
use Vidra\Financeiro\Domain\GeradorDePdfDaFatura;

final readonly class BaixarFatura
{
    public function __construct(private FaturaRepository $faturas, private GeradorDePdfDaFatura $gerador) {}

    /** @return array{nome: string, bytes: string} */
    public function __invoke(string $faturaId): array
    {
        $fatura = $this->faturas->porId($faturaId) ?? throw FaturaInvalida::com('Fatura não encontrada.');

        return ['nome' => 'fatura-'.$fatura->numeroFormatado().'.pdf', 'bytes' => $this->gerador->gerar($fatura)];
    }
}
