<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\PedidosDoVeterinario;

final readonly class PaginaDePedidosDto
{
    /** @param list<PedidoDoVeterinarioDto> $pedidos
     * @param array{andamento:int,prontosHoje:int,criticosHoje:int,mes:int} $resumo */
    public function __construct(public array $pedidos, public array $resumo, public int $total, public int $pagina, public int $paginas) {}
}
