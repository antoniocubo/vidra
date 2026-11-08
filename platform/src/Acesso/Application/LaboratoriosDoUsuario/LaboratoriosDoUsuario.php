<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application\LaboratoriosDoUsuario;

use Vidra\Acesso\Domain\Acessos;
use Vidra\Acesso\Domain\LaboratorioDoUsuario;

final readonly class LaboratoriosDoUsuario
{
    public function __construct(private Acessos $acessos) {}

    /** @return list<LaboratorioDoUsuario> */
    public function __invoke(int $usuarioId): array
    {
        return $this->acessos->laboratoriosDoUsuario($usuarioId);
    }
}
