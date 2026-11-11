<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Application\FecharFaturas;

use DateTimeImmutable;
use DateTimeZone;
use Vidra\Financeiro\Domain\Competencia;

/** "Fechar agora": o mês anterior de todos os pagadores, sem esperar o dia de fechamento de cada um. */
final readonly class FecharFaturasAgora
{
    public function __construct(private FechadorDeFaturas $fechador) {}

    public function __invoke(): int
    {
        $agora = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return $this->fechador->fechar(Competencia::anteriorA($agora), $agora, fn (): bool => true);
    }
}
