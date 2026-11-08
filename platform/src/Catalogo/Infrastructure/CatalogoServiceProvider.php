<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Infrastructure;

use Illuminate\Support\ServiceProvider;

final class CatalogoServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [\Vidra\Catalogo\Domain\Exame\ExameRepository::class => Persistence\EloquentExameRepository::class];
}
