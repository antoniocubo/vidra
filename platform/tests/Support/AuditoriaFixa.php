<?php

declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;
use Vidra\Auditoria\Application\RegistrarNaTrilha;
use Vidra\Auditoria\Domain\QuemFez;

final class AuditoriaFixa implements QuemFez
{
    public function usuarioId(): ?int
    {
        return 7;
    }

    public function nome(): string
    {
        return 'Marina Alves';
    }

    public function ip(): ?string
    {
        return '192.0.2.10';
    }

    public function agora(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-10T10:32:00-03:00');
    }

    public static function registrar(): RegistrarNaTrilha
    {
        return new RegistrarNaTrilha(new TrilhaEmMemoria, new self, new LaboratorioAtualFixo);
    }
}
