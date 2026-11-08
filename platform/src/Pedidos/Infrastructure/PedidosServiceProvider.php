<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Vidra\Pedidos\Domain\Amostra\AmostraRepository;
use Vidra\Pedidos\Domain\Pedido\PedidoRepository;
use Vidra\Pedidos\Domain\Portas\CatalogoDeExames;
use Vidra\Pedidos\Domain\Portas\GeradorDeCodigoDeBarras;
use Vidra\Pedidos\Domain\Relogio;
use Vidra\Pedidos\Infrastructure\Pedidos\CatalogoDeExamesDoLaboratorio;
use Vidra\Pedidos\Infrastructure\Persistence\EloquentAmostraRepository;
use Vidra\Pedidos\Infrastructure\Persistence\EloquentPedidoRepository;

/** Pacientes, Solicitantes, Pagadores e Vocabulario são ligados pelo provider da linha de produto. */
final class PedidosServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        PedidoRepository::class => EloquentPedidoRepository::class,
        AmostraRepository::class => EloquentAmostraRepository::class,
        CatalogoDeExames::class => CatalogoDeExamesDoLaboratorio::class,
        GeradorDeCodigoDeBarras::class => CodigoDeBarrasCode128::class,
        Relogio::class => RelogioSistema::class,
    ];
}
