<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CenarioDePainel
{
    /** @return array{pedido:string,item:string,amostra:string,exame:string} */
    public static function pedido(string $lab, int $numero, string $situacao = 'aguardando_amostra', string $aberto = '2026-11-08 12:00:00', int $prazo = 24, string $prioridade = 'normal'): array
    {
        $db = DB::connection(config()->string('vidra.conexao_sistema'));
        $pedido = (string) Str::uuid7();
        $item = (string) Str::uuid7();
        $amostra = (string) Str::uuid7();
        $exame = (string) Str::uuid7();
        $db->table('pedidos')->insert(['id' => $pedido, 'laboratorio_id' => $lab, 'protocolo' => $numero, 'paciente_id' => Str::uuid7(), 'solicitante_id' => Str::uuid7(), 'pagador_tipo' => 'responsavel', 'prioridade' => $prioridade, 'criado_em' => $aberto, 'criado_por' => 1]);
        $db->table('exames')->insert(['id' => $exame, 'laboratorio_id' => $lab, 'codigo' => 'E'.$numero, 'nome' => 'Exame '.$numero, 'setor' => 'hematologia', 'material' => 'Sangue', 'prazo_horas' => $prazo, 'preco_centavos' => 100, 'ativo' => true]);
        $db->table('pedido_itens')->insert(['id' => $item, 'laboratorio_id' => $lab, 'pedido_id' => $pedido, 'exame_id' => $exame, 'codigo' => 'E'.$numero, 'nome' => 'Exame '.$numero, 'setor' => 'hematologia', 'material' => 'Sangue', 'preco_tabela_centavos' => 100, 'preco_centavos' => 100, 'situacao' => $situacao]);
        $db->table('amostras')->insert(['id' => $amostra, 'laboratorio_id' => $lab, 'pedido_id' => $pedido, 'codigo' => 'A'.$numero, 'material' => 'Sangue', 'situacao' => $situacao === 'aguardando_amostra' ? 'aguardando' : 'recebida', 'recebida_em' => $situacao === 'aguardando_amostra' ? null : $aberto]);

        return compact('pedido', 'item', 'amostra', 'exame');
    }

    public static function laudo(string $lab, string $pedido, int $numero, string $quando = '2026-11-08 13:00:00'): void
    {
        DB::connection(config()->string('vidra.conexao_sistema'))->table('laudos')->insert(['id' => Str::uuid7(), 'laboratorio_id' => $lab, 'pedido_id' => $pedido, 'numero' => $numero, 'liberado_por' => 1, 'usuario_nome' => 'RT', 'rt_nome' => 'RT', 'rt_registro' => 'CRMV-SP 123', 'liberado_em' => $quando, 'codigo_acesso' => Str::upper(Str::random(9))]);
    }
}
