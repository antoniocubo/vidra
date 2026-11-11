<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * @property string $id
 * @property string $laboratorio_id
 * @property string $fatura_id
 * @property string $pedido_id
 * @property string $protocolo
 * @property string $paciente
 * @property \Illuminate\Support\Carbon $liberado_em
 * @property string $exames
 * @property int $valor_centavos
 */
final class LinhaFaturaModel extends Model
{
    use PertenceAoLaboratorio;

    public $timestamps = false;

    protected $table = 'linhas_fatura';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['liberado_em' => 'datetime', 'valor_centavos' => 'integer'];
    }
}
