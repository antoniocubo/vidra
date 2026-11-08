<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * @property string $id
 * @property string $laboratorio_id
 * @property string $analito_id
 * @property string $perfil
 * @property ?string $minimo
 * @property ?string $maximo
 * @property ?string $critico_baixo
 * @property ?string $critico_alto
 */
final class FaixaReferenciaModel extends Model
{
    use HasUuids;
    use PertenceAoLaboratorio;

    protected $table = 'faixas_referencia';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['minimo' => 'decimal:4', 'maximo' => 'decimal:4', 'critico_baixo' => 'decimal:4', 'critico_alto' => 'decimal:4'];
    }
}
