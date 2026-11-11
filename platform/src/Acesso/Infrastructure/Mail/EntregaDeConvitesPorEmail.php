<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure\Mail;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Vidra\Acesso\Domain\Convite;
use Vidra\Acesso\Domain\EntregaDeConvites;
use Vidra\Acesso\Domain\Equipe;

final readonly class EntregaDeConvitesPorEmail implements EntregaDeConvites
{
    public function __construct(private Equipe $equipe) {}

    public function enviar(Convite $convite, string $token): void
    {
        $mail = new ConviteMail($convite, $this->equipe->nomeDoUsuario($convite->convidadoPor), $this->equipe->nomeDoLaboratorio($convite->laboratorioId), route('convite.show', ['token' => $token]));
        DB::connection(config()->string('vidra.conexao_sistema'))->afterCommit(static fn () => Mail::to($convite->email)->send($mail));
    }
}
