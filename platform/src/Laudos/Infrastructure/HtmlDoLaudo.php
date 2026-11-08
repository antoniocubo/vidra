<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Vidra\Laudos\Domain\ConteudoDoLaudo;

final readonly class HtmlDoLaudo
{
    public function __invoke(ConteudoDoLaudo $laudo): string
    {
        $qr = (new Writer(new ImageRenderer(new RendererStyle(100), new SvgImageBackEnd)))->writeString($laudo->link);

        return view('laudos.pdf', ['laudo' => $laudo, 'qr' => 'data:image/svg+xml;base64,'.base64_encode($qr)])->render();
    }
}
