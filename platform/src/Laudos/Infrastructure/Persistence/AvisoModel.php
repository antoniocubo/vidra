<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * @property string $id
 * @property string $laboratorio_id
 * @property string $pedido_id
 * @property string $resultado_id
 * @property string $valor_avisado
 * @property \Illuminate\Support\Carbon $resultado_digitado_em
 * @property int $avisado_por
 * @property \Illuminate\Support\Carbon $avisado_em
 * @property string $destinatario
 * @property string $telefone
 */
final class AvisoModel extends Model
{
    use PertenceAoLaboratorio;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $table = 'avisos_valor_critico';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['resultado_digitado_em' => 'datetime', 'avisado_por' => 'integer', 'avisado_em' => 'datetime'];
    }
}
