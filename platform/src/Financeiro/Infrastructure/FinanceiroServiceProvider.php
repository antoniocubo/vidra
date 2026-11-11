<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Vidra\Financeiro\Domain\ArquivosDeFatura;
use Vidra\Financeiro\Domain\EmissorDeFatura;
use Vidra\Financeiro\Domain\FaturaRepository;
use Vidra\Financeiro\Domain\FaturasEmAtraso;
use Vidra\Financeiro\Domain\GeradorDePdfDaFatura;
use Vidra\Financeiro\Domain\PedidosFaturaveis;
use Vidra\Financeiro\Infrastructure\Persistence\EloquentFaturaRepository;

final class FinanceiroServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        FaturaRepository::class => EloquentFaturaRepository::class,
        FaturasEmAtraso::class => EloquentFaturaRepository::class,
        PedidosFaturaveis::class => PedidosFaturaveisEloquent::class,
        GeradorDePdfDaFatura::class => GotenbergGeradorDePdfDaFatura::class,
        ArquivosDeFatura::class => ArquivosDeFaturaLocal::class,
        EmissorDeFatura::class => EmissorDeFaturaPorEmail::class,
    ];
}
