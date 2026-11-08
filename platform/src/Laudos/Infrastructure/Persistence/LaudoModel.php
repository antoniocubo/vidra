<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * @property string $id
 * @property string $laboratorio_id
 * @property string $pedido_id
 * @property int $numero
 * @property int $liberado_por
 * @property string $usuario_nome
 * @property string $rt_nome
 * @property string $rt_registro
 * @property \Illuminate\Support\Carbon $liberado_em
 * @property ?string $observacao
 * @property ?string $pdf_caminho
 * @property ?string $pdf_erro
 * @property int $pdf_tentativas
 * @property string $codigo_acesso
 */
final class LaudoModel extends Model
{
    use PertenceAoLaboratorio;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $table = 'laudos';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['numero' => 'integer', 'liberado_por' => 'integer', 'liberado_em' => 'datetime'];
    }
}
