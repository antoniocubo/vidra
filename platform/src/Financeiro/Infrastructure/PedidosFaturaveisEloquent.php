<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Infrastructure;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Vidra\Financeiro\Domain\Competencia;
use Vidra\Financeiro\Domain\DadosDoPagador;
use Vidra\Financeiro\Domain\DadosDosPagadores;
use Vidra\Financeiro\Domain\FaturaPendente;
use Vidra\Financeiro\Domain\LinhaDaFatura;
use Vidra\Financeiro\Domain\PedidosFaturaveis;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

/**
 * Lê só as tabelas do núcleo (pedidos, itens e laudos). Entra o pedido com laudo liberado na competência,
 * pago por clínica ou solicitante, com ao menos um exame não cancelado, e que ainda não está em nenhuma fatura.
 */
final readonly class PedidosFaturaveisEloquent implements PedidosFaturaveis
{
    public function __construct(private LaboratorioAtual $atual, private DadosDosPagadores $pagadores) {}

    public function daCompetencia(Competencia $competencia): array
    {
        $lab = $this->atual->id();
        $pedidos = DB::table('laudos')->join('pedidos', 'pedidos.id', '=', 'laudos.pedido_id')
            ->where('laudos.laboratorio_id', $lab)
            // Sem limite inferior: pedido de mês anterior que ficou sem fatura entra na próxima.
            ->where('laudos.liberado_em', '<', $competencia->fim()->format('Y-m-d H:i:s'))
            ->whereIn('pedidos.pagador_tipo', ['clinica', 'solicitante'])
            ->whereNotIn('pedidos.id', DB::table('linhas_fatura')->where('laboratorio_id', $lab)->select('pedido_id'))
            ->get(['pedidos.id', 'pedidos.protocolo', 'pedidos.paciente_id', 'pedidos.solicitante_id', 'pedidos.clinica_id', 'pedidos.pagador_tipo', 'laudos.liberado_em']);
        if ($pedidos->isEmpty()) {
            return [];
        }

        $itens = DB::table('pedido_itens')->where('laboratorio_id', $lab)->whereIn('pedido_id', $pedidos->pluck('id'))->where('situacao', '<>', 'cancelado')
            ->orderBy('ordem')->get(['pedido_id', 'nome', 'preco_centavos'])->groupBy('pedido_id');
        $pacientes = $this->pagadores->pacientes(array_values(array_unique($pedidos->pluck('paciente_id')->all())));

        /** @var array<string, array{dados: DadosDoPagador, linhas: list<LinhaDaFatura>}> $porPagador */
        $porPagador = [];
        $conhecidos = [];
        foreach ($pedidos as $p) {
            $ativos = $itens->get($p->id);
            if ($ativos === null || $ativos->isEmpty()) {
                continue; // pedido cancelado por inteiro
            }
            $chave = $p->pagador_tipo.':'.($p->pagador_tipo === 'clinica' ? $p->clinica_id : $p->solicitante_id);
            $conhecidos[$chave] ??= $p->pagador_tipo === 'clinica' ? ($p->clinica_id === null ? null : $this->pagadores->clinica($p->clinica_id)) : $this->pagadores->solicitante($p->solicitante_id);
            if ($conhecidos[$chave] === null) {
                continue;
            }
            $porPagador[$chave]['dados'] = $conhecidos[$chave];
            $porPagador[$chave]['linhas'][] = new LinhaDaFatura(
                $p->id, 'P-'.str_pad((string) $p->protocolo, 5, '0', STR_PAD_LEFT), $pacientes[$p->paciente_id] ?? 'Paciente', new DateTimeImmutable($p->liberado_em, new DateTimeZone('UTC')),
                $ativos->pluck('nome')->implode(', '), (int) $ativos->sum('preco_centavos'),
            );
        }

        return array_values(array_map(fn (array $g): FaturaPendente => new FaturaPendente($g['dados'], $g['linhas']), $porPagador));
    }
}
