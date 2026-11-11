<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Login do veterinário no acesso vet. Separado de `users` (a equipe do
 * laboratório): outro guard, outra sessão, outro reset de senha.
 *
 * @property int $id
 * @property string $veterinario_id
 * @property string $email
 */
final class ContaVeterinario extends Authenticatable
{
    use Notifiable;

    protected $table = 'contas_veterinario';

    protected $fillable = ['veterinario_id', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
}
