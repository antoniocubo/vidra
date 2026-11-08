<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Vidra\Acesso\Domain\Acessos;
use Vidra\Acesso\Domain\LaboratorioRepository;
use Vidra\Acesso\Domain\Relogio;
use Vidra\Acesso\Infrastructure\Persistence\EloquentAcessos;
use Vidra\Acesso\Infrastructure\Persistence\EloquentLaboratorioRepository;

final class AcessoServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        LaboratorioRepository::class => EloquentLaboratorioRepository::class,
        Acessos::class => EloquentAcessos::class,
        Relogio::class => RelogioSistema::class,
    ];
}
