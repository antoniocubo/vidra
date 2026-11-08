<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Veterinario;

interface VeterinarioRepository
{
    public function nextIdentity(): VeterinarioId;

    public function save(Veterinario $veterinario): void;

    public function findByCrmv(Crmv $crmv): ?Veterinario;

    /**
     * @param  list<string>  $ids
     * @return array<string, Veterinario> indexados pelo id
     */
    public function porIds(array $ids): array;
}
