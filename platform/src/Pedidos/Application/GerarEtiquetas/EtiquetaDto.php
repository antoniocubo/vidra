<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\GerarEtiquetas;

final readonly class EtiquetaDto
{
    public function __construct(public string $codigo, public string $codigoDeBarrasSvg, public string $paciente, public string $especieCurta, public string $material, public string $protocolo) {}
}
