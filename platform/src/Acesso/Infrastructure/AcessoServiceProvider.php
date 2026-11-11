<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Vidra\Acesso\Domain\Acessos;
use Vidra\Acesso\Domain\LaboratorioRepository;
use Vidra\Acesso\Domain\Relogio;
use Vidra\Acesso\Infrastructure\Persistence\EloquentAcessos;
use Vidra\Acesso\Infrastructure\Persistence\EloquentLaboratorioRepository;

final class AcessoServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        \Vidra\Acesso\Domain\ConviteRepository::class => Persistence\EloquentConviteRepository::class,
        \Vidra\Acesso\Domain\Equipe::class => Persistence\EloquentEquipe::class,
        \Vidra\Acesso\Domain\ContasDeUsuario::class => Persistence\EloquentContasDeUsuario::class,
        \Vidra\Acesso\Domain\TransacaoDaEquipe::class => Persistence\TransacaoEloquentDaEquipe::class,
        \Vidra\Acesso\Domain\TokensDeConvite::class => TokensAleatoriosDeConvite::class,
        \Vidra\Acesso\Domain\EntregaDeConvites::class => Mail\EntregaDeConvitesPorEmail::class,
        \Vidra\Acesso\Domain\DadosDoRt::class => Persistence\EloquentDadosDoRt::class,
        LaboratorioRepository::class => EloquentLaboratorioRepository::class,
        Acessos::class => EloquentAcessos::class,
        Relogio::class => RelogioSistema::class,
    ];
}
