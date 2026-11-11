<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * @property string $id
 * @property string $laboratorio_id
 * @property string $laudo_id
 * @property int $versao
 * @property int $liberado_por
 * @property string $usuario_nome
 * @property string $rt_nome
 * @property string $rt_registro
 * @property \Illuminate\Support\Carbon $liberado_em
 * @property ?string $observacao
 * @property ?string $retificacao_motivo
 * @property ?\Illuminate\Support\Carbon $retificacao_em
 * @property ?string $pdf_caminho
 * @property ?string $conteudo
 */
final class VersaoLaudoModel extends Model
{
    use PertenceAoLaboratorio;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $table = 'versoes_laudo';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['versao' => 'integer', 'retificacao_em' => 'datetime', 'liberado_por' => 'integer', 'liberado_em' => 'datetime'];
    }
}
