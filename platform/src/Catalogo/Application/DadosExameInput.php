<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Application;

final readonly class DadosExameInput
{
    /** @param list<array{id?: ?string, nome: string, unidade: ?string, casas_decimais: int}> $analitos */
    public function __construct(public string $codigo, public string $nome, public string $setor, public string $material, public ?string $metodo, public int $prazoHoras, public int $precoCentavos, public array $analitos) {}
}
