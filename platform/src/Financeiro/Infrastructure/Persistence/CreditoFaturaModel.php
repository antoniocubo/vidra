<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * @property string $id
 * @property string $laboratorio_id
 * @property string $fatura_id
 * @property string $motivo
 * @property int $valor_centavos
 * @property int $por
 * @property \Illuminate\Support\Carbon $em
 */
final class CreditoFaturaModel extends Model
{
    use PertenceAoLaboratorio;

    public $timestamps = false;

    protected $table = 'creditos_fatura';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['valor_centavos' => 'integer', 'por' => 'integer', 'em' => 'datetime'];
    }
}
