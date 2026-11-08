<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Portas;

interface GeradorDeCodigoDeBarras
{
    /** Code 128 como SVG, pronto para ir dentro do HTML da etiqueta. */
    public function svg(string $texto): string;
}
