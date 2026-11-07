<?php

declare(strict_types=1);

namespace Vidra\Cadastros\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Vidra\Cadastros\Domain\Veterinario\VeterinarioRepository;
use Vidra\Cadastros\Infrastructure\Persistence\EloquentVeterinarioRepository;

final class CadastrosServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        VeterinarioRepository::class => EloquentVeterinarioRepository::class,
    ];
}
