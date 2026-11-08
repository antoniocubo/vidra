<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Str;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Veterinaria\Domain\Clinica\ClinicaId;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioId;
use Vidra\Veterinaria\Domain\Vinculo\Vinculo;
use Vidra\Veterinaria\Domain\Vinculo\VinculoId;
use Vidra\Veterinaria\Domain\Vinculo\VinculoRepository;

final class InMemoryVinculoRepository implements VinculoRepository
{
    /** @var array<string, Vinculo> */
    public array $items = [];

    public function __construct(private readonly LaboratorioAtual $laboratorio) {}

    public function nextIdentity(): VinculoId
    {
        return new VinculoId((string) Str::uuid7());
    }

    public function save(Vinculo $vinculo): void
    {
        $this->items[$vinculo->id->value] = $vinculo;
    }

    public function existe(VeterinarioId $veterinarioId): bool
    {
        foreach ($this->items as $vinculo) {
            if ($vinculo->laboratorioId === $this->laboratorio->id() && $vinculo->veterinarioId->value === $veterinarioId->value) {
                return true;
            }
        }

        return false;
    }

    public function porId(VinculoId $id): ?Vinculo
    {
        $vinculo = $this->items[$id->value] ?? null;

        return $vinculo !== null && $vinculo->laboratorioId === $this->laboratorio->id() ? $vinculo : null;
    }

    public function todos(): array
    {
        return array_values(array_filter($this->items, fn (Vinculo $v): bool => $v->laboratorioId === $this->laboratorio->id()));
    }

    public function daClinica(ClinicaId $clinicaId): array
    {
        return array_values(array_filter($this->todos(), fn (Vinculo $v): bool => $v->atendeEm($clinicaId->value)));
    }
}
