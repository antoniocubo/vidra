<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Acesso;

use DateTimeImmutable;

interface PedidosVisiveis
{
    /** @return array<string, DateTimeImmutable> IDs autorizados, do mais recente ao mais antigo. */
    public function doLaboratorio(LaboratorioDoVeterinario $acesso): array;
}
