<?php

declare(strict_types=1);

namespace Vidra\Bancada\Domain;

final readonly class Analito
{
    public function __construct(public string $id, public string $nome, public ?string $unidade, public int $casasDecimais, public ?Faixa $faixa) {}
}
