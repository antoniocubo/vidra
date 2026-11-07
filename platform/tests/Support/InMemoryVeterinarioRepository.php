<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Str;
use Vidra\Veterinaria\Domain\Veterinario\Crmv;
use Vidra\Veterinaria\Domain\Veterinario\Veterinario;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioId;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioRepository;

final class InMemoryVeterinarioRepository implements VeterinarioRepository
{
    /** @var array<string, Veterinario> */
    public array $items = [];

    public function nextIdentity(): VeterinarioId
    {
        return new VeterinarioId((string) Str::uuid7());
    }

    public function save(Veterinario $veterinario): void
    {
        $this->items[$veterinario->id->value] = $veterinario;
    }

    public function findByCrmv(Crmv $crmv): ?Veterinario
    {
        foreach ($this->items as $veterinario) {
            if ($veterinario->crmv->equals($crmv)) {
                return $veterinario;
            }
        }

        return null;
    }
}
