<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\RegistrarVeterinario;

use Vidra\Veterinaria\Domain\Veterinario\VeterinarioId;

final readonly class RegistrarVeterinarioResultado
{
    public function __construct(public VeterinarioId $id, public bool $reaproveitado, public string $nomeDeExibicao) {}
}
