<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Portas;

final readonly class ClinicaPagadora
{
    public function __construct(public string $id, public string $nome, public int $descontoPercentual, public bool $ativa = true) {}
}
