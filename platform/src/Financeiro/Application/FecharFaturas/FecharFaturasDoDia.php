<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Application\FecharFaturas;

use DateTimeImmutable;
use Vidra\Financeiro\Domain\Calendario;
use Vidra\Financeiro\Domain\Competencia;
use Vidra\Financeiro\Domain\FaturaPendente;

/** Rotina diária: fecha o mês anterior de quem tem o dia de fechamento hoje. */
final readonly class FecharFaturasDoDia
{
    public function __construct(private FechadorDeFaturas $fechador) {}

    public function __invoke(DateTimeImmutable $dia): int
    {
        return $this->fechador->fechar(
            Competencia::anteriorA($dia), $dia,
            fn (FaturaPendente $p): bool => Calendario::fechaNoDia($p->dados->diaDeFechamento, $dia),
        );
    }
}
