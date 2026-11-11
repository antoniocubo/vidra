<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Vidra\Financeiro\Infrastructure\Jobs\EnviarFaturaPorEmail;
use Vidra\Shared\Infrastructure\Tenancy\ComLaboratorio;

/** Gravações diretas de pedidos já liberados, prontos para entrar numa fatura. */
final class CenarioDeFatura
{
    /**
     * Um pedido com um item por preço informado. Sem `liberadoEm` não há laudo (pedido ainda não liberado).
     *
     * @param  array{animal: string, vinculo: string, clinica: string}  $c
     * @param  list<int>  $precos
     * @param  list<string>  $cancelados  nomes dos itens cancelados
     */
    public static function pedido(string $lab, array $c, int $protocolo, string $pagador = 'clinica', ?string $liberadoEm = '2026-11-10 15:00:00', array $precos = [9000], array $cancelados = []): string
    {
        $db = DB::connection(config()->string('vidra.conexao_sistema'));
        $id = (string) Str::uuid7();
        $db->table('pedidos')->insert([
            'id' => $id, 'laboratorio_id' => $lab, 'protocolo' => $protocolo, 'paciente_id' => $c['animal'], 'solicitante_id' => $c['vinculo'], 'pagador_tipo' => $pagador,
            'clinica_id' => $pagador === 'clinica' ? $c['clinica'] : null, 'prioridade' => 'rotina', 'criado_em' => '2026-11-09 12:00:00', 'criado_por' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ($precos as $i => $preco) {
            $nome = 'Exame '.($i + 1);
            $db->table('pedido_itens')->insert([
                'id' => (string) Str::uuid7(), 'laboratorio_id' => $lab, 'pedido_id' => $id, 'exame_id' => (string) Str::uuid7(), 'codigo' => 'EX'.$i, 'nome' => $nome, 'setor' => 'bioquimica', 'material' => 'Soro',
                'preco_tabela_centavos' => $preco + 1000, 'desconto_percentual' => 10, 'preco_centavos' => $preco, 'situacao' => in_array($nome, $cancelados, true) ? 'cancelado' : 'liberado', 'ordem' => $i,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        if ($liberadoEm !== null) {
            $db->table('laudos')->insert([
                'id' => (string) Str::uuid7(), 'laboratorio_id' => $lab, 'pedido_id' => $id, 'numero' => $protocolo, 'liberado_por' => 1, 'usuario_nome' => 'Marina', 'rt_nome' => 'Marina Alves',
                'rt_registro' => 'CRMV-SP 12345', 'liberado_em' => $liberadoEm, 'codigo_acesso' => strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4)), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $id;
    }

    /** Roda o job de envio como a fila rodaria: dentro do laboratório. */
    public static function enviar(EnviarFaturaPorEmail $job): void
    {
        app(ComLaboratorio::class)->handle($job, fn ($j) => app()->call([$j, 'handle']));
    }
}
