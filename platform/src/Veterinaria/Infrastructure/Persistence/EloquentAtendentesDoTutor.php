<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Persistence;

use App\Models\User;
use Vidra\Veterinaria\Domain\Tutor\AtendentesDoTutor;

final class EloquentAtendentesDoTutor implements AtendentesDoTutor
{
    public function nome(int $id): ?string
    {
        return User::query()->find($id)?->name;
    }
}
