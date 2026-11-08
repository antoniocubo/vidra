<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Animal;

use Vidra\Veterinaria\Domain\Tutor\TutorId;

interface AnimalRepository
{
    public function nextIdentity(): AnimalId;

    public function save(Animal $animal): void;

    public function porId(AnimalId $id): ?Animal;

    /** @return list<Animal> */
    public function doTutor(TutorId $id): array;
}
