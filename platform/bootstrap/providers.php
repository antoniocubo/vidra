<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
    Vidra\Acesso\Infrastructure\AcessoServiceProvider::class,
    Vidra\Bancada\Infrastructure\BancadaServiceProvider::class,
    Vidra\Catalogo\Infrastructure\CatalogoServiceProvider::class,
    Vidra\Laudos\Infrastructure\LaudosServiceProvider::class,
    Vidra\Painel\Infrastructure\PainelServiceProvider::class,
    Vidra\Pedidos\Infrastructure\PedidosServiceProvider::class,
    Vidra\Shared\Infrastructure\SharedServiceProvider::class,
    Vidra\Veterinaria\Infrastructure\VeterinariaServiceProvider::class,
];
