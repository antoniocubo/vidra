<?php

declare(strict_types=1);

namespace Vidra\Auditoria\Infrastructure\Persistence;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Str;
use Vidra\Auditoria\Domain\AcaoAuditada;
use Vidra\Auditoria\Domain\RegistroDeAuditoria;
use Vidra\Auditoria\Domain\TrilhaDeAuditoria;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;

final readonly class EloquentTrilhaDeAuditoria implements TrilhaDeAuditoria
{
    public function __construct(private ContextoLaboratorio $contexto) {}

    public function nextIdentity(): string
    {
        return (string) Str::uuid7();
    }

    public function registrar(RegistroDeAuditoria $r): void
    {
        $this->contexto->executarComo($r->laboratorioId, function () use ($r): void {
            RegistroDeAuditoriaModel::query()->create([
                'id' => $r->id, 'laboratorio_id' => $r->laboratorioId, 'acao' => $r->acao->value,
                'alvo_tipo' => $r->alvoTipo, 'alvo_id' => $r->alvoId, 'pedido_id' => $r->pedidoId,
                'usuario_id' => $r->usuarioId, 'usuario_nome' => $r->usuarioNome,
                'detalhe' => $r->detalhe, 'ip' => $r->ip, 'em' => $r->em->format('Y-m-d H:i:s.u'),
            ]);
        });
    }

    public function doPedido(string $pedidoId): array
    {
        return array_values(RegistroDeAuditoriaModel::query()->where('pedido_id', $pedidoId)->orderByDesc('em')->orderByDesc('id')->get()->map(self::restaurar(...))->all());
    }

    public function daEquipe(): array
    {
        return array_values(RegistroDeAuditoriaModel::query()->where('alvo_tipo', 'equipe')->orderByDesc('em')->orderByDesc('id')->limit(20)->get()->map(self::restaurar(...))->all());
    }

    private static function restaurar(RegistroDeAuditoriaModel $m): RegistroDeAuditoria
    {
        return new RegistroDeAuditoria((string) $m->getAttribute('id'), (string) $m->getAttribute('laboratorio_id'), AcaoAuditada::from((string) $m->getAttribute('acao')), (string) $m->getAttribute('alvo_tipo'), (string) $m->getAttribute('alvo_id'), $m->getAttribute('pedido_id'), $m->getAttribute('usuario_id') === null ? null : (int) $m->getAttribute('usuario_id'), (string) $m->getAttribute('usuario_nome'), (string) $m->getAttribute('detalhe'), $m->getAttribute('ip'), new DateTimeImmutable((string) $m->getAttribute('em'), new DateTimeZone('UTC')));
    }
}
