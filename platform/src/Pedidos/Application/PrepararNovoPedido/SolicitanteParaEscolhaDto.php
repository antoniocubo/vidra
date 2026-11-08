<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\PrepararNovoPedido;

use Vidra\Pedidos\Domain\Portas\ClinicaPagadora;

final readonly class SolicitanteParaEscolhaDto
{
    /** @param list<ClinicaPagadora> $clinicas */
    public function __construct(public string $id, public string $nome, public string $registro, public string $pagadorPadrao, public array $clinicas) {}
}
