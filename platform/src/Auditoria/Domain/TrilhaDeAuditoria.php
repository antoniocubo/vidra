<?php

declare(strict_types=1);

namespace Vidra\Auditoria\Domain;

interface TrilhaDeAuditoria
{
    public function nextIdentity(): string;

    public function registrar(RegistroDeAuditoria $registro): void;

    /** @return list<RegistroDeAuditoria> */
    public function doPedido(string $pedidoId): array;

    /** @return list<RegistroDeAuditoria> */
    public function daEquipe(): array;
}
