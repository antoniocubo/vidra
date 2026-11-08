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

    public function register(): void
    {
        $this->app->bind(\Vidra\Laudos\Domain\GeradorDePdf::class, GotenbergGeradorDePdf::class);
        $this->app->bind(\Vidra\Laudos\Domain\ArquivoDoLaudo::class, ArquivoDoLaudoLocal::class);
        $this->app->bind(\Vidra\Laudos\Domain\FonteDoLaudo::class, FonteDosPedidos::class);
        $this->app->bind(\Vidra\Laudos\Domain\AcessoPublico::class, AcessoPublicoDoLaudo::class);
        $this->app->bind(\Vidra\Laudos\Domain\EntregaDoPedido::class, EntregaDoPedidoEloquent::class);
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(\Vidra\Laudos\Domain\LaudoLiberado::class, DespacharEntregaDoLaudo::class);
    }
}
