<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure\Assinatura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Vidra\Acesso\Domain\Assinatura\PedidosDoMes;

/**
 * Lê `pedidos` e `pedido_itens` do núcleo por SQL (o Acesso não importa o domínio de Pedidos),
 * pela conexão de sistema e filtrando o laboratório. Cancelado por inteiro = nenhum item fora de "cancelado".
 */
final class PedidosDoMesPorSql implements PedidosDoMes
{
    public function contar(string $laboratorioId, int $ano, int $mes): int
    {
        $fuso = new DateTimeZone('America/Sao_Paulo');
        $inicio = new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $ano, $mes), $fuso);
        $utc = new DateTimeZone('UTC');

        return DB::connection(config()->string('vidra.conexao_sistema'))->table('pedidos')
            ->where('laboratorio_id', $laboratorioId)
            ->where('criado_em', '>=', $inicio->setTimezone($utc)->format('Y-m-d H:i:s'))
            ->where('criado_em', '<', $inicio->modify('+1 month')->setTimezone($utc)->format('Y-m-d H:i:s'))
            ->whereExists(static fn ($itens) => $itens->select(DB::raw('1'))->from('pedido_itens')
                ->whereColumn('pedido_itens.pedido_id', 'pedidos.id')->where('pedido_itens.situacao', '<>', 'cancelado'))
            ->count();
    }
}
