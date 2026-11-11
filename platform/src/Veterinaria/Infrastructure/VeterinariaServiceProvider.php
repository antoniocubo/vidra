<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Vidra\Shared\Domain\Transacao;
use Vidra\Shared\Infrastructure\TransacaoEloquent;
use Vidra\Veterinaria\Domain\Clinica\ClinicaRepository;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioRepository;
use Vidra\Veterinaria\Domain\Vinculo\VinculoRepository;
use Vidra\Veterinaria\Infrastructure\Persistence\EloquentClinicaRepository;
use Vidra\Veterinaria\Infrastructure\Persistence\EloquentVeterinarioRepository;
use Vidra\Veterinaria\Infrastructure\Persistence\EloquentVinculoRepository;

final class VeterinariaServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        VeterinarioRepository::class => EloquentVeterinarioRepository::class,
        VinculoRepository::class => EloquentVinculoRepository::class,
        ClinicaRepository::class => EloquentClinicaRepository::class,
        \Vidra\Veterinaria\Domain\Tutor\TutorRepository::class => Persistence\EloquentTutorRepository::class,
        \Vidra\Veterinaria\Domain\Animal\AnimalRepository::class => Persistence\EloquentAnimalRepository::class,
        \Vidra\Veterinaria\Domain\Tutor\AtendentesDoTutor::class => Persistence\EloquentAtendentesDoTutor::class,
        \Vidra\Veterinaria\Domain\Vinculo\VinculosParaEscolha::class => EloquentVinculoRepository::class,
        \Vidra\Veterinaria\Domain\Relogio::class => RelogioSistema::class,
        \Vidra\Catalogo\Domain\Referencia\PerfisDeReferencia::class => Referencia\PerfisVeterinarios::class,
        \Vidra\Pedidos\Domain\Portas\Pacientes::class => Pedidos\PacientesVeterinaria::class,
        \Vidra\Pedidos\Domain\Portas\Solicitantes::class => Pedidos\SolicitantesVeterinaria::class,
        \Vidra\Pedidos\Domain\Portas\Pagadores::class => Pedidos\PagadoresVeterinaria::class,
        \Vidra\Pedidos\Domain\Portas\Vocabulario::class => Pedidos\VocabularioVeterinaria::class,
        \Vidra\Financeiro\Domain\DadosDosPagadores::class => Financeiro\DadosDosPagadoresVeterinaria::class,
        Transacao::class => TransacaoEloquent::class,
        \Vidra\Veterinaria\Domain\Acesso\AcessosDoVeterinario::class => Acesso\EloquentAcessosDoVeterinario::class,
        \Vidra\Veterinaria\Domain\Acesso\PedidosVisiveis::class => Acesso\EloquentPedidosVisiveis::class,
        \Vidra\Veterinaria\Domain\Acesso\DownloadVeterinario::class => Acesso\DownloadVeterinarioEloquent::class,
        \Vidra\Veterinaria\Domain\Acesso\ConvitesDoVeterinario::class => Acesso\EloquentConvitesDoVeterinario::class,
        \Vidra\Veterinaria\Domain\Acesso\EntregaDeAcessoVeterinario::class => Mail\EntregaDeAcessoPorEmail::class,
        \Vidra\Veterinaria\Domain\Acesso\DadosDoAcesso::class => Acesso\DadosDoAcessoEloquent::class,
    ];

    public function boot(): void
    {
        \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(function (\Illuminate\Contracts\Auth\CanResetPassword $conta, string $token): string {
            if ($conta instanceof \App\Models\ContaVeterinario) {
                return route('vet.senha.nova', ['token' => $token, 'email' => $conta->getEmailForPasswordReset()]);
            }

            return route('password.reset', ['token' => $token, 'email' => $conta->getEmailForPasswordReset()]);
        });
    }
}
