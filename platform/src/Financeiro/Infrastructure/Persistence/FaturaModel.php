<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * @property string $id
 * @property string $laboratorio_id
 * @property int $numero
 * @property string $pagador_tipo
 * @property string $pagador_id
 * @property string $pagador_nome
 * @property ?string $pagador_email
 * @property string $competencia
 * @property string $vencimento
 * @property \Illuminate\Support\Carbon $fechada_em
 * @property ?\Illuminate\Support\Carbon $enviada_em
 * @property ?\Illuminate\Support\Carbon $paga_em
 * @property ?int $paga_por
 * @property ?string $pdf_caminho
 * @property \Illuminate\Database\Eloquent\Collection<int, LinhaFaturaModel> $linhas
 * @property \Illuminate\Database\Eloquent\Collection<int, CreditoFaturaModel> $creditos
 */
final class FaturaModel extends Model
{
    use PertenceAoLaboratorio;

    protected $table = 'faturas';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['numero' => 'integer', 'paga_por' => 'integer', 'fechada_em' => 'datetime', 'enviada_em' => 'datetime', 'paga_em' => 'datetime'];
    }

    /** @return HasMany<LinhaFaturaModel, $this> */
    public function linhas(): HasMany
    {
        return $this->hasMany(LinhaFaturaModel::class, 'fatura_id')->orderBy('liberado_em')->orderBy('protocolo');
    }

    /** @return HasMany<CreditoFaturaModel, $this> */
    public function creditos(): HasMany
    {
        return $this->hasMany(CreditoFaturaModel::class, 'fatura_id')->orderBy('em');
    }
}
