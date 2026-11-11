<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Acesso;

/**
 * Os vínculos de um veterinário em todos os laboratórios. Lida antes de existir
 * um laboratório atual, então a implementação usa a conexão de sistema.
 */
interface AcessosDoVeterinario
{
    /** @return list<LaboratorioDoVeterinario> em ordem alfabética */
    public function laboratoriosDo(string $veterinarioId): array;
}
