<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

/**
 * Quem entra em qual laboratório. Lê o mapa de acessos, que não pertence a um
 * laboratório só: por isso a implementação não passa pelo filtro de tenancy.
 */
interface Acessos
{
    /** @return list<LaboratorioDoUsuario> em ordem alfabética */
    public function laboratoriosDoUsuario(int $usuarioId): array;
}
