<?php

declare(strict_types=1);

namespace Vidra\Shared\Infrastructure\Tenancy;

use Closure;

final readonly class ComLaboratorio
{
    public function __construct(private ContextoLaboratorio $contexto) {}

    /** @param object{laboratorioId:string} $job */
    public function handle(object $job, Closure $next): mixed
    {
        return $this->contexto->executarComo($job->laboratorioId, fn () => $next($job));
    }
}
