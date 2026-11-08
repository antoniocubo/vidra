<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Portas;

interface Solicitantes
{
    public function resumo(string $solicitanteId): ?SolicitanteDoPedido;

    /**
     * @param  list<string>  $solicitanteIds
     * @return array<string, SolicitanteDoPedido> indexados pelo id
     */
    public function resumos(array $solicitanteIds): array;

    /** @return list<SolicitanteDoPedido> por nome */
    public function todos(): array;
}
