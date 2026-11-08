<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * @property string $id
 * @property string $laboratorio_id
 * @property string $laudo_id
 * @property string $ip
 * @property string $user_agent
 */
final class AcessoModel extends Model
{
    use PertenceAoLaboratorio;

    protected $table = 'acessos_laudo';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['quando' => 'datetime'];
    }
}
