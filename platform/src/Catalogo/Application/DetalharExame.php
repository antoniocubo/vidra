<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Application;

use Vidra\Catalogo\Domain\Exame\Exame;
use Vidra\Catalogo\Domain\Referencia\PerfilDeReferencia;
use Vidra\Catalogo\Domain\Referencia\PerfisDeReferencia;

final readonly class DetalharExame
{
    public function __construct(private ObterExame $obter, private PerfisDeReferencia $perfis) {}

    /** @return array{exame:ExameDto,perfis:list<PerfilDeReferencia>} */
    public function __invoke(string $id): array
    {
        return ['exame' => new ExameDto(($this->obter)($id)), 'perfis' => $this->perfis->todos()];
    }
}
