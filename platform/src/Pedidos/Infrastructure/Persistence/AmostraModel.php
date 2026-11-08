<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * @property string $id
 * @property string $laboratorio_id
 * @property string $pedido_id
 * @property string $codigo
 * @property string $material
 * @property string $situacao
 * @property \Carbon\CarbonImmutable|null $recebida_em
 * @property int|null $recebida_por
 * @property string|null $motivo_rejeicao
 * @property string|null $motivo_texto
 * @property string|null $ressalva
 * @property string|null $recoleta_codigo
 */
final class AmostraModel extends Model
{
    use HasUuids;
    use PertenceAoLaboratorio;

    protected $table = 'amostras';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['recebida_em' => 'immutable_datetime', 'recebida_por' => 'integer'];
    }
}
