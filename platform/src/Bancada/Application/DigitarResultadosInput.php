<?php

declare(strict_types=1);

namespace Vidra\Bancada\Application;

final readonly class DigitarResultadosInput
{
    /**
     * @param array<string,array{valor:?string,texto:?string}> $valores */
    public function __construct(public string $itemId, public array $valores, public int $por, public string $nome) {}
}
