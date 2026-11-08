<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Vidra\Laudos\Domain\Conferencia;
use Vidra\Laudos\Domain\LaudoRepository;
use Vidra\Laudos\Domain\ResponsavelTecnico;
use Vidra\Laudos\Infrastructure\Persistence\EloquentLaudoRepository;

final class LaudosServiceProvider extends ServiceProvider
{
    /** @var array<class-string,class-string> */
    public array $bindings = [LaudoRepository::class => EloquentLaudoRepository::class, Conferencia::class => ConferenciaDosPedidos::class, ResponsavelTecnico::class => ResponsavelTecnicoDoAcesso::class];
}
