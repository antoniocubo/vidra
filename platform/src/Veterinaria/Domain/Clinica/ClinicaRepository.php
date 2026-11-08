<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Clinica;

use Vidra\Shared\Domain\ValueObjects\Cnpj;

interface ClinicaRepository
{
    public function nextIdentity(): ClinicaId;

    public function save(Clinica $clinica): void;

    /** Só enxerga o laboratório atual. */
    public function porId(ClinicaId $id): ?Clinica;

    public function porCnpj(Cnpj $cnpj): ?Clinica;

    /**
     * @param  list<string>  $ids
     * @return array<string, Clinica> indexadas pelo id
     */
    public function porIds(array $ids): array;

    /** @return list<Clinica> ativas e inativas do laboratório atual */
    public function todas(): array;
}
