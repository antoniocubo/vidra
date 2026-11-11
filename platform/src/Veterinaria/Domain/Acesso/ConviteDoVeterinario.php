<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Acesso;

use DateTimeImmutable;

final readonly class ConviteDoVeterinario
{
    private function __construct(public string $id, public string $laboratorioId, public string $veterinarioId, public string $tokenHash, public int $convidadoPor, public DateTimeImmutable $expiraEm, public ?DateTimeImmutable $aceitoEm, public ?DateTimeImmutable $canceladoEm) {}

    public static function criar(string $id, string $laboratorio, string $veterinario, string $hash, int $por, DateTimeImmutable $agora): self
    {
        return new self($id, $laboratorio, $veterinario, $hash, $por, $agora->modify('+7 days'), null, null);
    }

    public static function restaurar(string $id, string $laboratorio, string $veterinario, string $hash, int $por, DateTimeImmutable $expira, ?DateTimeImmutable $aceito, ?DateTimeImmutable $cancelado): self
    {
        return new self($id, $laboratorio, $veterinario, $hash, $por, $expira, $aceito, $cancelado);
    }

    public function conferir(DateTimeImmutable $agora): void
    {
        if ($this->aceitoEm !== null || $this->canceladoEm !== null || $agora >= $this->expiraEm) {
            throw new AcessoVeterinarioInvalido('Este convite venceu ou já foi usado. Peça um novo acesso ao laboratório.');
        }
    }
}
