<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Str;
use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Veterinaria\Domain\Tutor\Tutor;
use Vidra\Veterinaria\Domain\Tutor\TutorId;
use Vidra\Veterinaria\Domain\Tutor\TutorRepository;

final class InMemoryTutorRepository implements TutorRepository
{
    /** @var array<string, Tutor> */
    public array $items = [];

    public function nextIdentity(): TutorId
    {
        return new TutorId((string) Str::uuid7());
    }

    public function save(Tutor $t): void
    {
        $this->items[$t->id->value] = clone $t;
    }

    public function porId(TutorId $id): ?Tutor
    {
        return isset($this->items[$id->value]) ? clone $this->items[$id->value] : null;
    }

    public function porCelular(Telefone $celular): ?Tutor
    {
        foreach ($this->items as $t) {
            if ($t->celular()->digits === $celular->digits) {
                return clone $t;
            }
        }

        return null;
    }

    public function buscar(string $termo, int $limite): array
    {
        return array_slice(array_values(array_filter($this->items, fn (Tutor $t): bool => str_contains(mb_strtolower($t->nome()), mb_strtolower($termo)))), 0, $limite);
    }
}
