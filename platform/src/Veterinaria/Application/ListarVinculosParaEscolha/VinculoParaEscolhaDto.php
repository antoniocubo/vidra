<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\ListarVinculosParaEscolha;

final readonly class VinculoParaEscolhaDto
{
    public function __construct(public string $id, public string $nome) {}
}
