<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

interface AcessoPublico
{
    public function abrir(CodigoDeAcesso $codigo, string $ip, string $agente): ConteudoDoLaudo;

    public function pdf(CodigoDeAcesso $codigo): DownloadDoLaudo;
}
