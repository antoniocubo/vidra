<?php

declare(strict_types=1);

namespace Vidra\Bancada\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * @property string $id
 * @property string $laboratorio_id
 * @property string $pedido_id
 * @property string $item_id
 * @property string $paciente_id
 * @property string $analito_id
 * @property string $nome
 * @property ?string $unidade
 * @property ?string $valor
 * @property ?string $texto
 * @property int $digitado_por
 * @property string $digitador_nome
 * @property \Illuminate\Support\Carbon $digitado_em
 * @property ?string $perfil
 * @property ?string $minimo
 * @property ?string $maximo
 * @property ?string $critico_baixo
 * @property ?string $critico_alto
 * @property string $classificacao
 */
final class ResultadoModel extends Model
{
    use PertenceAoLaboratorio;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $table = 'resultados';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['digitado_por' => 'integer', 'digitado_em' => 'datetime'];
    }
}
