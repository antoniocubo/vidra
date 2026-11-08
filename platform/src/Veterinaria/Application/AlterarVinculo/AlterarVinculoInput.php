<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\AlterarVinculo;

final readonly class AlterarVinculoInput
{
    /** @param list<string> $clinicaIds */
    public function __construct(
        public string $vinculoId,
        public string $telefoneCritico,
        public string $quemPaga,
        public string $recebeLaudo,
        public bool $vePedidosDosColegas,
        public array $clinicaIds = [],
    ) {}
}
