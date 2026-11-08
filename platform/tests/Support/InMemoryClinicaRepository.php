<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Str;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Shared\Domain\ValueObjects\Cnpj;
use Vidra\Veterinaria\Domain\Clinica\Clinica;
use Vidra\Veterinaria\Domain\Clinica\ClinicaId;
use Vidra\Veterinaria\Domain\Clinica\ClinicaRepository;

final class InMemoryClinicaRepository implements ClinicaRepository
{
    /** @var array<string, Clinica> */
    public array $items = [];

    public function __construct(private readonly LaboratorioAtual $laboratorio) {}

    public function nextIdentity(): ClinicaId
    {
        return new ClinicaId((string) Str::uuid7());
    }

    public function save(Clinica $clinica): void
    {
        $this->items[$clinica->id->value] = $clinica;
    }

    public function porId(ClinicaId $id): ?Clinica
    {
        return $this->todasDoLaboratorio()[$id->value] ?? null;
    }

    public function porCnpj(Cnpj $cnpj): ?Clinica
    {
        foreach ($this->todasDoLaboratorio() as $clinica) {
            if ($clinica->dados()->cnpj?->value === $cnpj->value) {
                return $clinica;
            }
        }

        return null;
    }

    public function porIds(array $ids): array
    {
        return array_intersect_key($this->todasDoLaboratorio(), array_flip($ids));
    }

    public function todas(): array
    {
        return array_values($this->todasDoLaboratorio());
    }

    /** @return array<string, Clinica> */
    private function todasDoLaboratorio(): array
    {
        return array_filter($this->items, fn (Clinica $c): bool => $c->laboratorioId === $this->laboratorio->id());
    }
}
