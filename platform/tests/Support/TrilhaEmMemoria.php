<?php

declare(strict_types=1);

namespace Tests\Support;

use Vidra\Auditoria\Domain\RegistroDeAuditoria;
use Vidra\Auditoria\Domain\TrilhaDeAuditoria;

final class TrilhaEmMemoria implements TrilhaDeAuditoria
{
    /** @var list<RegistroDeAuditoria> */
    public array $registros = [];

    public function nextIdentity(): string
    {
        return (string) (count($this->registros) + 1);
    }

    public function registrar(RegistroDeAuditoria $registro): void
    {
        $this->registros[] = $registro;
    }

    public function doPedido(string $pedidoId): array
    {
        return array_values(array_filter(array_reverse($this->registros), fn ($r) => $r->pedidoId === $pedidoId));
    }

    public function daEquipe(): array
    {
        return array_slice(array_values(array_filter(array_reverse($this->registros), fn ($r) => $r->alvoTipo === 'equipe')), 0, 20);
    }
}
