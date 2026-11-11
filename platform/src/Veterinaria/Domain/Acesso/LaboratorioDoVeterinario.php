<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Acesso;

/** Um laboratório em que o veterinário tem vínculo, visto pelo acesso vet. */
final readonly class LaboratorioDoVeterinario
{
    public function __construct(
        public string $laboratorioId,
        public string $nome,
        public string $vinculoId,
        public bool $vePedidosDosColegas,
    ) {}
}
