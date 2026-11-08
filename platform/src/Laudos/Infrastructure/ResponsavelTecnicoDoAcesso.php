<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure;

use Vidra\Acesso\Application\DadosDoResponsavelTecnico;
use Vidra\Acesso\Application\LaboratoriosDoUsuario\LaboratoriosDoUsuario;
use Vidra\Laudos\Domain\ResponsavelTecnico;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class ResponsavelTecnicoDoAcesso implements ResponsavelTecnico
{
    public function __construct(private LaboratoriosDoUsuario $acessos, private DadosDoResponsavelTecnico $dadosRt, private LaboratorioAtual $atual) {}

    public function ehRt(int $usuarioId): bool
    {
        foreach (($this->acessos)($usuarioId) as $l) {
            if ($l->id->value === $this->atual->id()) {
                return $l->eRt;
            }
        }

        return false;
    }

    public function dados(): array
    {
        return ($this->dadosRt)();
    }
}
