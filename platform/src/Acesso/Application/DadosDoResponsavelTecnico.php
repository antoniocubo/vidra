<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application;

use Vidra\Acesso\Domain\DadosDoRt;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class DadosDoResponsavelTecnico
{
    public function __construct(private DadosDoRt $dados, private LaboratorioAtual $atual) {}

    /** @return array{nome:string,registro:string} */
    public function __invoke(): array
    {
        return $this->dados->doLaboratorio($this->atual->id());
    }
}
