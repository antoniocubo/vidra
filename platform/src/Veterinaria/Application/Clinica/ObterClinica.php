<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\Clinica;

use Vidra\Veterinaria\Domain\Clinica\Clinica;
use Vidra\Veterinaria\Domain\Clinica\ClinicaId;
use Vidra\Veterinaria\Domain\Clinica\ClinicaRepository;
use Vidra\Veterinaria\Domain\Clinica\Exceptions\ClinicaNaoEncontrada;

/** Busca a clínica do laboratório atual ou lança ClinicaNaoEncontrada (o id de outro laboratório cai aqui). */
final readonly class ObterClinica
{
    public function __construct(private ClinicaRepository $clinicas) {}

    public function __invoke(string $id): Clinica
    {
        return $this->clinicas->porId(new ClinicaId($id)) ?? throw ClinicaNaoEncontrada::para();
    }
}
