<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure\Persistence;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use SensitiveParameter;
use Vidra\Acesso\Domain\ContasDeUsuario;

final class EloquentContasDeUsuario implements ContasDeUsuario
{
    public function existe(string $email): bool
    {
        return User::on(config()->string('vidra.conexao_sistema'))->whereRaw('lower(email) = ?', [strtolower(trim($email))])->exists();
    }

    public function criar(string $nome, string $email, #[SensitiveParameter] string $senha): int
    {
        $user = new User;
        $user->setConnection(config()->string('vidra.conexao_sistema'));
        $user->name = $nome;
        $user->email = $email;
        $user->password = Hash::make($senha);
        $user->setAttribute('email_verified_at', now());
        $user->save();

        return $user->id;
    }
}
