<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

final class VinculoModel extends Model
{
    use HasUuids;
    use PertenceAoLaboratorio;

    protected $table = 'laboratorio_veterinario';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['ve_pedidos_dos_colegas' => 'boolean'];
    }
}
