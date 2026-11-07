<?php

declare(strict_types=1);

namespace Vidra\Cadastros\Domain\Veterinario;

interface VeterinarioRepository
{
    public function nextIdentity(): VeterinarioId;

    public function save(Veterinario $veterinario): void;

    public function findByCrmv(Crmv $crmv): ?Veterinario;
}
