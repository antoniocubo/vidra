<?php

declare(strict_types=1);

namespace Vidra\Cadastros\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent só para persistência. Regras de negócio ficam na entidade Veterinario.
 *
 * @property string $id
 * @property string $tratamento
 * @property string $nome
 * @property string $crmv_numero
 * @property string $crmv_uf
 * @property string $email
 * @property string $celular
 * @property string $telefone_critico
 * @property string|null $especialidade
 */
final class VeterinarioModel extends Model
{
    use HasUuids;

    protected $table = 'veterinarios';

    protected $guarded = [];
}
