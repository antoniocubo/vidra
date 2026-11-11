<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Vidra\Laudos\Domain\AvisoDeValorCritico;
use Vidra\Laudos\Domain\CodigoDeAcesso;
use Vidra\Laudos\Domain\Laudo;
use Vidra\Laudos\Domain\LaudoRepository;
use Vidra\Laudos\Domain\Retificacao;
use Vidra\Laudos\Domain\VersaoDoLaudo;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class EloquentLaudoRepository implements LaudoRepository
{
    public function __construct(private LaboratorioAtual $atual) {}

    public function nextIdentity(): string
    {
        return (string) Str::uuid7();
    }

    public function proximoNumero(): int
    {
        $chave = ['laboratorio_id' => $this->atual->id(), 'nome' => 'laudo'];
        DB::table('sequencias')->insertOrIgnore([...$chave, 'valor' => 0]);
        DB::table('sequencias')->where($chave)->lockForUpdate()->first();
        DB::table('sequencias')->where($chave)->increment('valor');

        return (int) DB::table('sequencias')->where($chave)->value('valor');
    }

    public function codigoExiste(CodigoDeAcesso $codigo): bool
    {
        return DB::connection(config()->string('vidra.conexao_sistema'))->table('laudos')->where('codigo_acesso', $codigo->value)->exists();
    }

    public function save(Laudo $l): void
    {
        $anterior = LaudoModel::query()->find($l->id);
        $campos = ['versao' => $l->versao];
        foreach (['retificacao' => $l->retificacao, 'reaberto' => $l->reaberto] as $prefixo => $r) {
            $campos[$prefixo.'_motivo'] = $r?->motivo;
            $campos[$prefixo.'_por'] = $r?->por;
            $campos[$prefixo.'_em'] = $r?->em;
        }
        if ($anterior !== null && $anterior->versao !== $l->versao) {
            $campos += ['pdf_caminho' => null, 'pdf_erro' => null, 'pdf_gerado_em' => null, 'pdf_tentativas' => 0];
        }
        LaudoModel::query()->updateOrCreate(['id' => $l->id], $campos + ['id' => $l->id, 'laboratorio_id' => $l->laboratorioId, 'pedido_id' => $l->pedidoId, 'numero' => $l->numero, 'liberado_por' => $l->liberadoPor, 'usuario_nome' => $l->usuarioNome, 'rt_nome' => $l->rtNome, 'rt_registro' => $l->rtRegistro, 'liberado_em' => $l->liberadoEm, 'observacao' => $l->observacao, 'codigo_acesso' => $l->codigoAcesso->value]);
        VersaoLaudoModel::query()->firstOrCreate(['laudo_id' => $l->id, 'versao' => $l->versao], ['id' => $this->nextIdentity(), 'liberado_por' => $l->liberadoPor, 'usuario_nome' => $l->usuarioNome, 'rt_nome' => $l->rtNome, 'rt_registro' => $l->rtRegistro, 'liberado_em' => $l->liberadoEm, 'observacao' => $l->observacao, 'retificacao_motivo' => $l->retificacao?->motivo, 'retificacao_em' => $l->retificacao?->em]);
    }

    public function doPedido(string $pedidoId): ?Laudo
    {
        $m = LaudoModel::query()->where('pedido_id', $pedidoId)->first();

        return $m === null ? null : Laudo::restaurar($m->id, $m->laboratorio_id, $m->pedido_id, $m->numero, $m->liberado_por, $m->usuario_nome, $m->rt_nome, $m->rt_registro, $m->liberado_em->toDateTimeImmutable(), $m->observacao, new CodigoDeAcesso($m->codigo_acesso), $this->avisos($pedidoId), $m->versao, $m->retificacao_motivo === null ? null : new Retificacao($m->retificacao_motivo, $m->retificacao_por ?? 0, $m->retificacao_em?->toDateTimeImmutable() ?? $m->liberado_em->toDateTimeImmutable()), $m->reaberto_motivo === null ? null : new Retificacao($m->reaberto_motivo, $m->reaberto_por ?? 0, $m->reaberto_em?->toDateTimeImmutable() ?? $m->liberado_em->toDateTimeImmutable()));
    }

    public function versoes(string $laudoId): array
    {
        return array_values(VersaoLaudoModel::query()->where('laudo_id', $laudoId)->orderBy('versao')->get()->map(fn (VersaoLaudoModel $v): VersaoDoLaudo => new VersaoDoLaudo($v->versao, $v->liberado_em->toDateTimeImmutable(), $v->usuario_nome, $v->retificacao_motivo, $v->pdf_caminho))->all());
    }

    public function saveAviso(AvisoDeValorCritico $a): void
    {
        AvisoModel::query()->updateOrCreate(['resultado_id' => $a->resultadoId], ['id' => $a->id, 'laboratorio_id' => $a->laboratorioId, 'pedido_id' => $a->pedidoId, 'valor_avisado' => $a->valorAvisado, 'resultado_digitado_em' => $a->resultadoDigitadoEm, 'avisado_por' => $a->avisadoPor, 'avisado_em' => $a->avisadoEm, 'destinatario' => $a->destinatario, 'telefone' => $a->telefone]);
    }

    public function avisos(string $pedidoId): array
    {
        return array_values(AvisoModel::query()->where('pedido_id', $pedidoId)->get()->map(fn (AvisoModel $m): AvisoDeValorCritico => new AvisoDeValorCritico($m->id, $m->laboratorio_id, $m->pedido_id, $m->resultado_id, $m->valor_avisado, $m->resultado_digitado_em->toDateTimeImmutable(), $m->avisado_por, $m->avisado_em->toDateTimeImmutable(), $m->destinatario, $m->telefone))->all());
    }
}
