<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Portas;

interface Pagadores
{
    /** @return list<ClinicaPagadora> as ativas onde o solicitante atende */
    public function clinicasDoSolicitante(string $solicitanteId): array;

    /** Ativa ou não: um pedido antigo ainda precisa mostrar a clínica que pagava. */
    public function clinica(string $id): ?ClinicaPagadora;

    /**
     * @param  list<string>  $ids
     * @return array<string, ClinicaPagadora> indexadas pelo id
     */
    public function clinicas(array $ids): array;

    /** @return list<ClinicaPagadora> por nome */
    public function todasAtivas(): array;
}
