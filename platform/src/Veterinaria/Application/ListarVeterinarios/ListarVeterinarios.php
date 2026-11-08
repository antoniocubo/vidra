<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\ListarVeterinarios;

use Vidra\Veterinaria\Application\Busca\TextoDeBusca;
use Vidra\Veterinaria\Domain\Vinculo\VinculoRepository;

final readonly class ListarVeterinarios
{
    public function __construct(private VinculoRepository $vinculos, private MontarVeterinariosDto $montar) {}

    /** @return list<VeterinarioDto> por nome, filtrados por nome, CRMV ou nome da clínica */
    public function __invoke(string $busca = ''): array
    {
        $dtos = array_filter(
            ($this->montar)($this->vinculos->todos()),
            fn (VeterinarioDto $v): bool => TextoDeBusca::casa($busca, [$v->nomeCompleto, $v->crmv, ...array_map(fn (ClinicaDoVeterinarioDto $c): string => $c->nome, $v->clinicas)]),
        );
        usort($dtos, fn (VeterinarioDto $a, VeterinarioDto $b): int => strcmp(TextoDeBusca::normalizar($a->nomeCompleto), TextoDeBusca::normalizar($b->nomeCompleto)));

        return $dtos;
    }
}
