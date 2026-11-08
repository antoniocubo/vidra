<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * Liga a amostra aos itens do pedido que a usam (chave composta: só insere e apaga).
 *
 * @property string $laboratorio_id
 * @property string $amostra_id
 * @property string $item_id
 */
final class AmostraItemModel extends Model
{
    use PertenceAoLaboratorio;

    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'amostra_itens';

    protected $guarded = [];
}
