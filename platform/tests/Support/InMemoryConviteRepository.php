<?php

declare(strict_types=1);

namespace Tests\Support;

use Vidra\Acesso\Domain\Convite;
use Vidra\Acesso\Domain\ConviteRepository;

final class InMemoryConviteRepository implements ConviteRepository
{
    /** @var array<string,Convite> */
    public array $dados = [];

    private int $sequencia = 0;

    public function nextIdentity(): string
    {
        return 'convite-'.++$this->sequencia;
    }

    public function save(Convite $convite): void
    {
        $this->dados[$convite->id] = clone $convite;
    }

    public function findById(string $laboratorioId, string $id): ?Convite
    {
        $convite = $this->dados[$id] ?? null;

        return $convite?->laboratorioId === $laboratorioId ? clone $convite : null;
    }

    public function findByTokenHash(string $hash): ?Convite
    {
        foreach ($this->dados as $convite) {
            if ($convite->tokenHash === $hash) {
                return clone $convite;
            }
        }

        return null;
    }

    public function pendentes(string $laboratorioId): array
    {
        return array_values(array_map(static fn (Convite $c): Convite => clone $c, array_filter($this->dados, static fn (Convite $c): bool => $c->laboratorioId === $laboratorioId && $c->aceitoEm === null && $c->canceladoEm === null)));
    }
}
