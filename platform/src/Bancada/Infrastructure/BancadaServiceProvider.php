<?php

declare(strict_types=1);

namespace Vidra\Bancada\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Vidra\Bancada\Domain\Referencias;
use Vidra\Bancada\Domain\ResultadoRepository;
use Vidra\Bancada\Domain\Trabalhos;
use Vidra\Bancada\Infrastructure\Persistence\EloquentResultadoRepository;

final class BancadaServiceProvider extends ServiceProvider
{
    /** @var array<class-string,class-string> */
    public array $bindings = [ResultadoRepository::class => EloquentResultadoRepository::class, Trabalhos::class => TrabalhosDosPedidos::class, Referencias::class => ReferenciasDoCatalogo::class];
}
