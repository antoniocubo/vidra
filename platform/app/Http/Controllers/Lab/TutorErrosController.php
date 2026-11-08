<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use Illuminate\Validation\ValidationException;
use Vidra\Shared\Domain\DomainException;
use Vidra\Veterinaria\Domain\Tutor\Exceptions\CelularJaCadastrado;
use Vidra\Veterinaria\Domain\Tutor\Exceptions\TutorNaoEncontrado;
use Vidra\Veterinaria\Domain\Tutor\Exceptions\TutorSemEmail;

final class TutorErrosController
{
    public static function traduzir(DomainException $e, string $prefixoAnimal = ''): never
    {
        if ($e instanceof TutorNaoEncontrado) {
            abort(404);
        }
        $campo = match (true) {
            $e instanceof CelularJaCadastrado => 'celular', $e instanceof TutorSemEmail => 'email', default => 'form'
        };
        foreach (['vinculo_veterinario_id', 'nascimento', 'peso', 'microchip'] as $animalCampo) {
            if (str_starts_with($e->getMessage(), $animalCampo.':')) {
                $campo = $prefixoAnimal.$animalCampo;
            }
        }
        throw ValidationException::withMessages([$campo => $e->getMessage()]);
    }
}
