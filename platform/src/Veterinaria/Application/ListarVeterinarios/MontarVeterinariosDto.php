<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\ListarVeterinarios;

use Vidra\Veterinaria\Domain\Clinica\ClinicaRepository;
use Vidra\Veterinaria\Domain\Veterinario\Veterinario;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioRepository;
use Vidra\Veterinaria\Domain\Vinculo\Vinculo;

/** Junta vínculo, veterinário e clínicas em poucas consultas, sem uma por linha. */
final readonly class MontarVeterinariosDto
{
    public function __construct(private VeterinarioRepository $veterinarios, private ClinicaRepository $clinicas) {}

    /**
     * @param  list<Vinculo>  $vinculos
     * @return list<VeterinarioDto>
     */
    public function __invoke(array $vinculos): array
    {
        $veterinarios = $this->veterinarios->porIds(array_map(fn (Vinculo $v): string => $v->veterinarioId->value, $vinculos));
        $clinicas = $this->clinicas->porIds(array_merge(...array_map(fn (Vinculo $v): array => $v->clinicaIds, $vinculos)));

        $dtos = [];
        foreach ($vinculos as $vinculo) {
            $veterinario = $veterinarios[$vinculo->veterinarioId->value] ?? null;
            if (! $veterinario instanceof Veterinario) {
                continue;
            }
            $doVinculo = [];
            foreach ($vinculo->clinicaIds as $clinicaId) {
                if (isset($clinicas[$clinicaId])) {
                    $doVinculo[] = new ClinicaDoVeterinarioDto($clinicaId, $clinicas[$clinicaId]->dados()->nomeFantasia, $clinicas[$clinicaId]->ativa());
                }
            }
            usort($doVinculo, fn (ClinicaDoVeterinarioDto $a, ClinicaDoVeterinarioDto $b): int => strcmp($a->nome, $b->nome));

            $dtos[] = new VeterinarioDto(
                vinculoId: $vinculo->id->value,
                nome: $veterinario->nomeDeExibicao(),
                nomeCompleto: $veterinario->nome(),
                especialidade: $veterinario->especialidade(),
                crmv: (string) $veterinario->crmv,
                email: $veterinario->email()->value,
                celular: $veterinario->celular()->formatted(),
                telefoneCritico: $vinculo->telefoneCritico->formatted(),
                quemPaga: $vinculo->quemPaga->value,
                recebeLaudo: $vinculo->recebeLaudo->value,
                vePedidosDosColegas: $vinculo->vePedidosDosColegas,
                clinicas: $doVinculo,
            );
        }

        return $dtos;
    }
}
