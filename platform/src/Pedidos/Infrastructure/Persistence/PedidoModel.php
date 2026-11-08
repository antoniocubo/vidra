<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * @property string $id
 * @property string $laboratorio_id
 * @property int $protocolo
 * @property string $paciente_id
 * @property string $solicitante_id
 * @property string $pagador_tipo
 * @property string|null $clinica_id
 * @property string $prioridade
 * @property string|null $observacao
 * @property \Carbon\CarbonImmutable $criado_em
 * @property int $criado_por
 */
final class PedidoModel extends Model
{
    use HasUuids;
    use PertenceAoLaboratorio;

    protected $table = 'pedidos';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['protocolo' => 'integer', 'criado_em' => 'immutable_datetime', 'criado_por' => 'integer'];
    }
}
