<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * @property string $id
 * @property string $laboratorio_id
 * @property string $exame_id
 * @property string $nome
 * @property ?string $unidade
 * @property int $casas_decimais
 * @property int $ordem
 */
final class AnalitoModel extends Model
{
    use HasUuids;
    use PertenceAoLaboratorio;

    protected $table = 'analitos';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['casas_decimais' => 'integer', 'ordem' => 'integer'];
    }
}
