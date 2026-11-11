<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

use DateTimeImmutable;
use Vidra\Shared\Domain\ValueObjects\Email;

final class Convite
{
    private function __construct(
        public readonly string $id, public readonly string $laboratorioId,
        public readonly string $nome, public readonly string $email, public readonly Papel $papel,
        public readonly string $tokenHash, public readonly int $convidadoPor,
        public readonly DateTimeImmutable $expiraEm,
        public private(set) ?DateTimeImmutable $aceitoEm,
        public private(set) ?DateTimeImmutable $canceladoEm,
    ) {}

    public static function emitir(string $id, string $laboratorioId, string $nome, string $email, Papel $papel, string $token, int $convidadoPor, DateTimeImmutable $agora): self
    {
        return new self($id, $laboratorioId, trim($nome), (new Email($email))->value, $papel, hash('sha256', $token), $convidadoPor, $agora->modify('+7 days'), null, null);
    }

    public static function restaurar(string $id, string $laboratorioId, string $nome, string $email, Papel $papel, string $tokenHash, int $convidadoPor, DateTimeImmutable $expiraEm, ?DateTimeImmutable $aceitoEm, ?DateTimeImmutable $canceladoEm): self
    {
        return new self($id, $laboratorioId, $nome, $email, $papel, $tokenHash, $convidadoPor, $expiraEm, $aceitoEm, $canceladoEm);
    }

    public function conferirValidade(DateTimeImmutable $agora): void
    {
        if ($this->aceitoEm !== null || $this->canceladoEm !== null || $this->expiraEm <= $agora) {
            throw ConviteInvalido::para();
        }
    }

    public function aceitar(DateTimeImmutable $agora): void
    {
        $this->conferirValidade($agora);
        $this->aceitoEm = $agora;
    }

    public function cancelar(DateTimeImmutable $agora): void
    {
        if ($this->aceitoEm !== null || $this->canceladoEm !== null) {
            throw ConviteInvalido::para();
        }
        $this->canceladoEm = $agora;
    }
}
