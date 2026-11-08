<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\ListarVinculosParaEscolha;

use Vidra\Veterinaria\Domain\Vinculo\VinculosParaEscolha;

final readonly class ListarVinculosParaEscolha
{
    public function __construct(private VinculosParaEscolha $vinculos) {}

    /** @return list<VinculoParaEscolhaDto> */
    public function __invoke(): array
    {
        $resultado = [];
        foreach ($this->vinculos->nomes() as $id => $nome) {
            $resultado[] = new VinculoParaEscolhaDto($id, $nome);
        }

        return $resultado;
    }
}
