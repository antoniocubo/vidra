<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * Onde o veterinário atende: liga o vínculo (veterinário no laboratório) às clínicas.
 *
 * @property int $id
 * @property string $laboratorio_id
 * @property string $clinica_id
 * @property string $vinculo_id
 */
final class ClinicaVeterinarioModel extends Model
{
    use PertenceAoLaboratorio;

    protected $table = 'clinica_veterinario';

    protected $guarded = [];
}
