<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Application;

final readonly class DefinirFaixasInput
{
    /** @param list<array{perfil: string,minimo: ?string,maximo: ?string,criticoBaixo: ?string,criticoAlto: ?string}> $faixas */
    public function __construct(public string $analitoId, public array $faixas) {}
}
