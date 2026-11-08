<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\DetalharClinica;

use Vidra\Veterinaria\Application\Busca\TextoDeBusca;
use Vidra\Veterinaria\Application\Clinica\ClinicaDto;
use Vidra\Veterinaria\Application\Clinica\ObterClinica;
use Vidra\Veterinaria\Application\Clinica\VeterinarioDaClinicaDto;
use Vidra\Veterinaria\Domain\Veterinario\Veterinario;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioRepository;
use Vidra\Veterinaria\Domain\Vinculo\Vinculo;
use Vidra\Veterinaria\Domain\Vinculo\VinculoRepository;

final readonly class DetalharClinica
{
    public function __construct(private ObterClinica $obter, private VinculoRepository $vinculos, private VeterinarioRepository $veterinarios) {}

    public function __invoke(string $clinicaId): ClinicaDto
    {
        $clinica = ($this->obter)($clinicaId);
        $vinculos = $this->vinculos->daClinica($clinica->id);
        $veterinarios = $this->veterinarios->porIds(array_map(fn (Vinculo $v): string => $v->veterinarioId->value, $vinculos));

        $lista = [];
        foreach ($vinculos as $vinculo) {
            $veterinario = $veterinarios[$vinculo->veterinarioId->value] ?? null;
            if ($veterinario instanceof Veterinario) {
                $lista[] = new VeterinarioDaClinicaDto($vinculo->id->value, $veterinario->nomeDeExibicao());
            }
        }
        usort($lista, fn (VeterinarioDaClinicaDto $a, VeterinarioDaClinicaDto $b): int => strcmp(TextoDeBusca::normalizar($a->nome), TextoDeBusca::normalizar($b->nome)));

        return ClinicaDto::de($clinica, $lista);
    }
}
