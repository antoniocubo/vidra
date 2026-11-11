<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;
use Vidra\Bancada\Infrastructure\Persistence\ResultadoModel;
use Vidra\Laudos\Domain\ConsultaDeLaudos as Porta;
use Vidra\Laudos\Infrastructure\Persistence\AcessoModel;
use Vidra\Laudos\Infrastructure\Persistence\EntregaModel;
use Vidra\Laudos\Infrastructure\Persistence\LaudoModel;
use Vidra\Pedidos\Application\Texto;
use Vidra\Pedidos\Domain\Portas\Pacientes;
use Vidra\Pedidos\Domain\Portas\Solicitantes;

final readonly class ConsultaDeLaudos implements Porta
{
    public function __construct(private Pacientes $pacientes, private Solicitantes $solicitantes) {}

    public function pagina(string $laboratorioId, string $busca, ?DateTimeImmutable $de, ?DateTimeImmutable $ate, string $entrega, int $pagina): array
    {
        $emails = EntregaModel::query()->where('canal', 'email')->select('laudo_id')
            ->selectRaw("MAX(CASE WHEN situacao = 'enviado' THEN 1 ELSE 0 END) AS email_ok")
            ->selectRaw("MAX(CASE WHEN situacao = 'falhou' THEN 1 ELSE 0 END) AS email_falhou")->groupBy('laudo_id');
        $acessos = AcessoModel::query()->select('laudo_id')->selectRaw('COUNT(*) AS acessos, MAX(quando) AS ultimo_acesso')->groupBy('laudo_id');
        $criticos = ResultadoModel::query()->whereIn('classificacao', ['critico_alto', 'critico_baixo'])->select('pedido_id')->selectRaw('COUNT(*) AS criticos')->groupBy('pedido_id');
        $q = LaudoModel::query()->where('laudos.laboratorio_id', $laboratorioId)->whereNotNull('laudos.liberado_em')
            ->join('pedidos', function ($join) use ($laboratorioId): void {
                $join->on('pedidos.id', '=', 'laudos.pedido_id')->where('pedidos.laboratorio_id', $laboratorioId);
            })
            ->leftJoinSub($emails, 'emails', 'emails.laudo_id', '=', 'laudos.id')
            ->leftJoinSub($acessos, 'acessos', 'acessos.laudo_id', '=', 'laudos.id')
            ->leftJoinSub($criticos, 'criticos', 'criticos.pedido_id', '=', 'laudos.pedido_id')
            ->select('laudos.*', 'pedidos.protocolo', 'pedidos.paciente_id', 'pedidos.solicitante_id', 'emails.email_ok', 'emails.email_falhou', 'acessos.acessos', 'acessos.ultimo_acesso', 'criticos.criticos');
        if ($de !== null) {
            $q->where('laudos.liberado_em', '>=', $de->format('Y-m-d H:i:s'));
        }
        if ($ate !== null) {
            $q->where('laudos.liberado_em', '<', $ate->format('Y-m-d H:i:s'));
        }
        match ($entrega) {
            'entregue' => $q->where('email_ok', 1),
            'falhou' => $q->where('email_falhou', 1),
            'pendente' => $q->whereNull('emails.laudo_id'),
            'nao_aberto' => $q->where('email_ok', 1)->whereNull('acessos.laudo_id'),
            default => null,
        };
        if ($busca !== '') {
            $pacientes = array_column(array_filter($this->pacientes->buscar($busca, PHP_INT_MAX), fn ($p): bool => Texto::casa($busca, [$p->nome])), 'id');
            $solicitantes = array_column(array_filter($this->solicitantes->todos(), fn ($s): bool => Texto::casa($busca, [$s->nome])), 'id');
            $numero = preg_match('/^(?:[LP]-)?0*(\d+)(?: v\d+)?$/i', $busca, $m) === 1 ? (int) $m[1] : null;
            $q->where(function (Builder $q) use ($pacientes, $solicitantes, $numero, $busca): void {
                $q->whereIn('pedidos.paciente_id', $pacientes)->orWhereIn('pedidos.solicitante_id', $solicitantes);
                if ($numero !== null) {
                    if (! str_starts_with(strtoupper($busca), 'P-')) {
                        $q->orWhere('laudos.numero', $numero);
                    }
                    if (! str_starts_with(strtoupper($busca), 'L-')) {
                        $q->orWhere('pedidos.protocolo', $numero);
                    }
                }
            });
        }
        $models = $q->orderByDesc('laudos.liberado_em')->orderByDesc('laudos.id')->offset(($pagina - 1) * 25)->limit(26)->get();
        $pacientes = $this->pacientes->resumos(array_values(array_unique($models->pluck('paciente_id')->all())));
        $solicitantes = $this->solicitantes->resumos(array_values(array_unique($models->pluck('solicitante_id')->all())));
        $linhas = [];
        foreach ($models as $l) {
            $linhas[] = [
                'id' => $l->id,
                'pedidoId' => $l->pedido_id,
                'numero' => 'L-'.str_pad((string) $l->numero, 5, '0', STR_PAD_LEFT).($l->versao > 1 ? ' v'.$l->versao : ''),
                'protocolo' => 'P-'.str_pad((string) $l->getAttribute('protocolo'), 5, '0', STR_PAD_LEFT),
                'paciente' => $pacientes[(string) $l->getAttribute('paciente_id')]->nome ?? 'Paciente removido',
                'solicitante' => $solicitantes[(string) $l->getAttribute('solicitante_id')]->nome ?? 'Solicitante removido',
                'liberadoEm' => $l->liberado_em->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i'),
                'rt' => $l->rt_nome,
                'retificado' => $l->versao > 1,
                'emRetificacao' => $l->reaberto_em !== null,
                'temValorCritico' => (int) $l->getAttribute('criticos') > 0,
                'emailOk' => (bool) $l->getAttribute('email_ok'),
                'emailFalhou' => (bool) $l->getAttribute('email_falhou'),
                'acessos' => (int) $l->getAttribute('acessos'),
                'ultimoAcesso' => $l->getAttribute('ultimo_acesso') === null ? null : (new DateTimeImmutable((string) $l->getAttribute('ultimo_acesso'), new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i'),
            ];
        }

        return $linhas;
    }
}
