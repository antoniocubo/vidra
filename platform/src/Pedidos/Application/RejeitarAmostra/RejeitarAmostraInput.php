<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\RejeitarAmostra;

final readonly class RejeitarAmostraInput
{
    public function __construct(public string $amostraId, public string $motivo, public ?string $texto = null) {}
}
