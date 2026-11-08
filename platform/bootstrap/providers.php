<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    Vidra\Shared\Infrastructure\SharedServiceProvider::class,
    Vidra\Acesso\Infrastructure\AcessoServiceProvider::class,
    Vidra\Veterinaria\Infrastructure\VeterinariaServiceProvider::class,
];
