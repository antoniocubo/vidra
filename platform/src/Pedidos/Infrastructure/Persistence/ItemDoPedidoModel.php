<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * @property string $id
 * @property string $laboratorio_id
 * @property string $pedido_id
 * @property string $exame_id
 * @property string $codigo
 * @property string $nome
 * @property string $setor
 * @property string $material
 * @property int $preco_tabela_centavos
 * @property int $desconto_percentual
 * @property int $preco_centavos
 * @property string $situacao
 * @property int $ordem
 */
final class ItemDoPedidoModel extends Model
{
    use HasUuids;
    use PertenceAoLaboratorio;

    protected $table = 'pedido_itens';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['preco_tabela_centavos' => 'integer', 'desconto_percentual' => 'integer', 'preco_centavos' => 'integer', 'ordem' => 'integer'];
    }
}
