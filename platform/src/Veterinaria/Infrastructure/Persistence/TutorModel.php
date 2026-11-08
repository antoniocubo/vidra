<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * @property string $id
 * @property string $laboratorio_id
 * @property string $nome
 * @property string $celular
 * @property string|null $email
 * @property string|null $cpf
 * @property string|null $cep
 * @property \Carbon\CarbonImmutable|null $email_autorizado_em
 * @property int|null $email_autorizado_por
 */
final class TutorModel extends Model
{
    use HasUuids;
    use PertenceAoLaboratorio;

    protected $table = 'tutores';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['email_autorizado_em' => 'immutable_datetime', 'email_autorizado_por' => 'integer'];
    }
}
