<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Infrastructure;

use Picqer\Barcode\BarcodeGeneratorSVG;
use Vidra\Pedidos\Domain\Portas\GeradorDeCodigoDeBarras;

final class CodigoDeBarrasCode128 implements GeradorDeCodigoDeBarras
{
    public function svg(string $texto): string
    {
        // Largura 1 e altura 40 viram o desenho; a etiqueta o estica com CSS (preserveAspectRatio none).
        return (new BarcodeGeneratorSVG)->getBarcode($texto, BarcodeGeneratorSVG::TYPE_CODE_128, 1, 40);
    }
}
