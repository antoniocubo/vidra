<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

interface ConviteRepository
{
    public function nextIdentity(): string;

    public function save(Convite $convite): void;

    public function findById(string $laboratorioId, string $id): ?Convite;

    public function findByTokenHash(string $hash): ?Convite;

    /** @return list<Convite> Convites ainda não aceitos nem cancelados, inclusive expirados para permitir reenvio. */
    public function pendentes(string $laboratorioId): array;
}
