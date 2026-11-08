<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Tutor;

use Vidra\Shared\Domain\ValueObjects\Telefone;

interface TutorRepository
{
    public function nextIdentity(): TutorId;

    public function save(Tutor $tutor): void;

    public function porId(TutorId $id): ?Tutor;

    public function porCelular(Telefone $celular): ?Tutor;

    /** @return list<Tutor> */
    public function buscar(string $termo, int $limite): array;
}
