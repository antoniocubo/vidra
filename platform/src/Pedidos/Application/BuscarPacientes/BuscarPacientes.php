<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\BuscarPacientes;

use Vidra\Pedidos\Domain\Portas\PacienteDoPedido;
use Vidra\Pedidos\Domain\Portas\Pacientes;

final readonly class BuscarPacientes
{
    public function __construct(private Pacientes $pacientes) {}

    /** @return list<PacienteDoPedido> */
    public function __invoke(string $termo): array
    {
        return trim($termo) === '' ? [] : $this->pacientes->buscar($termo, 12);
    }
}
