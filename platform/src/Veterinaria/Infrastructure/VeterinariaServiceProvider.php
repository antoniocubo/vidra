<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Vidra\Shared\Domain\Transacao;
use Vidra\Shared\Infrastructure\TransacaoEloquent;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioRepository;
use Vidra\Veterinaria\Domain\Vinculo\VinculoRepository;
use Vidra\Veterinaria\Infrastructure\Persistence\EloquentVeterinarioRepository;
use Vidra\Veterinaria\Infrastructure\Persistence\EloquentVinculoRepository;

final class VeterinariaServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        VeterinarioRepository::class => EloquentVeterinarioRepository::class,
        VinculoRepository::class => EloquentVinculoRepository::class,
        Transacao::class => TransacaoEloquent::class,
    ];
}
