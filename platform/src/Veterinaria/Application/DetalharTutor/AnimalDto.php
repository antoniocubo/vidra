<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\DetalharTutor;

final readonly class AnimalDto
{
    public function __construct(public string $id, public string $nome, public string $descricao, public ?string $veterinario, public ?string $perfil, public string $nascimento, public ?string $microchip) {}
}
