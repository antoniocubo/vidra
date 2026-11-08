<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application;

final readonly class LiberarLaudoInput
{
    public function __construct(public string $pedidoId, public int $por, public string $nome, public ?string $observacao) {}
}
