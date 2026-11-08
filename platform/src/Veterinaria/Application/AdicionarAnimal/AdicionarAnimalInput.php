<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\AdicionarAnimal;

/** @phpstan-type AnimalDados array{nome: string, especie: string, raca: ?string, sexo: string, nascimento: string, nascimentoEstimado: bool, pesoGramas: ?int, castrado: string, microchip: ?string, vinculoVeterinarioId: ?string} */
final readonly class AdicionarAnimalInput
{
    /** @param AnimalDados $animal */
    public function __construct(public string $tutorId, public array $animal) {}
}
