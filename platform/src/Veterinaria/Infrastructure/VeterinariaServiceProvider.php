<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioRepository;
use Vidra\Veterinaria\Infrastructure\Persistence\EloquentVeterinarioRepository;

final class VeterinariaServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        VeterinarioRepository::class => EloquentVeterinarioRepository::class,
    ];
}
