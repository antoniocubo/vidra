<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Str;
use Vidra\Veterinaria\Domain\Animal\Animal;
use Vidra\Veterinaria\Domain\Animal\AnimalId;
use Vidra\Veterinaria\Domain\Animal\AnimalRepository;
use Vidra\Veterinaria\Domain\Tutor\TutorId;

final class InMemoryAnimalRepository implements AnimalRepository
{
    /** @var array<string, Animal> */
    public array $items = [];

    public function nextIdentity(): AnimalId
    {
        return new AnimalId((string) Str::uuid7());
    }

    public function save(Animal $a): void
    {
        $this->items[$a->id->value] = $a;
    }

    public function porId(AnimalId $id): ?Animal
    {
        return $this->items[$id->value] ?? null;
    }

    public function doTutor(TutorId $id): array
    {
        return array_values(array_filter($this->items, fn (Animal $a): bool => $a->tutorId->value === $id->value));
    }
}
