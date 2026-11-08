<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * @property string $id
 * @property string $laboratorio_id
 * @property string $tutor_id
 * @property string $nome
 * @property string $especie
 * @property string|null $raca
 * @property string $sexo
 * @property \Carbon\CarbonImmutable $nascimento
 * @property bool $nascimento_estimado
 * @property int|null $peso_gramas
 * @property string $castrado
 * @property string|null $microchip
 * @property string|null $vinculo_veterinario_id
 */
final class AnimalModel extends Model
{
    use HasUuids;
    use PertenceAoLaboratorio;

    protected $table = 'animais';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['nascimento' => 'immutable_date', 'nascimento_estimado' => 'boolean', 'peso_gramas' => 'integer'];
    }
}
