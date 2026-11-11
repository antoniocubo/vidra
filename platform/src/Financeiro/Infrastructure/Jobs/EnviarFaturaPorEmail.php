<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Infrastructure\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Vidra\Financeiro\Application\EnviarFatura\EnviarFatura;
use Vidra\Shared\Infrastructure\Tenancy\ComLaboratorio;

final class EnviarFaturaPorEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public string $laboratorioId, public string $faturaId)
    {
        $this->afterCommit();
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [app(ComLaboratorio::class), (new WithoutOverlapping('fatura:'.$this->laboratorioId.':'.$this->faturaId))->releaseAfter(30)->expireAfter(75)];
    }

    public function handle(EnviarFatura $enviar): void
    {
        $enviar($this->faturaId);
    }
}
