<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * @property string $id
 * @property string $laboratorio_id
 * @property string $codigo
 * @property string $nome
 * @property string $setor
 * @property string $material
 * @property ?string $metodo
 * @property int $prazo_horas
 * @property int $preco_centavos
 * @property bool $ativo
 */
final class ExameModel extends Model
{
    use HasUuids;
    use PertenceAoLaboratorio;

    protected $table = 'exames';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['ativo' => 'boolean', 'prazo_horas' => 'integer', 'preco_centavos' => 'integer'];
    }
}
