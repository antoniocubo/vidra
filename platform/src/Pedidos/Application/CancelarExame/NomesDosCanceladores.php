<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\CancelarExame;

use Closure;

/** Consulta os nomes sem acoplar o detalhe do pedido ao cadastro de usuários. */
final readonly class NomesDosCanceladores
{
    /** @param Closure(list<int>): array<int, string> $buscar */
    public function __construct(private Closure $buscar) {}

    /**
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    public function nomesPorIds(array $ids): array
    {
        return ($this->buscar)($ids);
    }
}
