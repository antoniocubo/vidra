<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Vidra\Acesso\Domain\Acessos;
use Vidra\Acesso\Domain\Assinatura\AssinaturaRepository;
use Vidra\Acesso\Domain\Assinatura\AvisosDeAssinatura;
use Vidra\Acesso\Domain\Assinatura\CobrancasDoVidra;
use Vidra\Acesso\Domain\Assinatura\GatewayDeCobranca;
use Vidra\Acesso\Domain\Assinatura\PedidosDoMes;
use Vidra\Acesso\Domain\LaboratorioRepository;
use Vidra\Acesso\Domain\Relogio;
use Vidra\Acesso\Infrastructure\Asaas\AsaasGateway;
use Vidra\Acesso\Infrastructure\Asaas\GatewayDeCobrancaNulo;
use Vidra\Acesso\Infrastructure\Assinatura\EloquentAssinaturaRepository;
use Vidra\Acesso\Infrastructure\Assinatura\EloquentCobrancasDoVidra;
use Vidra\Acesso\Infrastructure\Assinatura\PedidosDoMesPorSql;
use Vidra\Acesso\Infrastructure\Mail\AvisosDeAssinaturaPorEmail;
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
        AssinaturaRepository::class => EloquentAssinaturaRepository::class,
        CobrancasDoVidra::class => EloquentCobrancasDoVidra::class,
        PedidosDoMes::class => PedidosDoMesPorSql::class,
        AvisosDeAssinatura::class => AvisosDeAssinaturaPorEmail::class,
    ];

    public function register(): void
    {
        // Sem chave (ambiente local) o gateway só registra no log.
        $this->app->bind(GatewayDeCobranca::class, static fn (): GatewayDeCobranca => config('services.asaas.chave')
            ? new AsaasGateway(config()->string('services.asaas.chave'), config()->string('services.asaas.ambiente'))
            : new GatewayDeCobrancaNulo);
    }
}
