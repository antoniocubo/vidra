<?php

declare(strict_types=1);

namespace Vidra\Shared\Infrastructure\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

/**
 * Primeira defesa da tenancy (a segunda é o RLS): toda consulta do model filtra
 * pelo laboratório atual, e todo registro novo nasce nele. Sem laboratório
 * definido, a consulta lança LaboratorioNaoDefinido em vez de devolver tudo.
 *
 * @mixin Model
 */
trait PertenceAoLaboratorio
{
    public static function bootPertenceAoLaboratorio(): void
    {
        static::addGlobalScope('laboratorio', static function (Builder $consulta): void {
            $consulta->where($consulta->qualifyColumn('laboratorio_id'), app(LaboratorioAtual::class)->id());
        });

        static::creating(static function (Model $model): void {
            if ($model->getAttribute('laboratorio_id') === null) {
                $model->setAttribute('laboratorio_id', app(LaboratorioAtual::class)->id());
            }
        });
    }
}
