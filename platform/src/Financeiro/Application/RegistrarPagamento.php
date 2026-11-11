<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Application;

use DateTimeImmutable;
use DateTimeZone;
use Vidra\Auditoria\Application\RegistrarNaTrilha;
use Vidra\Financeiro\Domain\FaturaInvalida;
use Vidra\Financeiro\Domain\FaturaRepository;

final readonly class RegistrarPagamento
{
    public function __construct(private FaturaRepository $faturas, private RegistrarNaTrilha $trilha) {}

    public function __invoke(string $faturaId, int $por): void
    {
        $fatura = $this->faturas->porId($faturaId) ?? throw FaturaInvalida::com('Fatura não encontrada.');
        $fatura->registrarPagamento(new DateTimeImmutable('now', new DateTimeZone('UTC')), $por);
        $this->faturas->save($fatura);
        ($this->trilha)('pagamento_registrado', 'fatura', $faturaId, 'Registrou o pagamento da fatura');
    }
}
