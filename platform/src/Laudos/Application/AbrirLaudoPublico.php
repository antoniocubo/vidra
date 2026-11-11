<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application;

use Vidra\Laudos\Domain\AcessoPublico;
use Vidra\Laudos\Domain\CodigoDeAcesso;
use Vidra\Laudos\Domain\ConteudoDoLaudo;
use Vidra\Laudos\Domain\DownloadDoLaudo;

final readonly class AbrirLaudoPublico
{
    public function __construct(private AcessoPublico $acesso) {}

    public function __invoke(string $codigo, string $ip, string $agente): ConteudoDoLaudo
    {
        return $this->acesso->abrir(CodigoDeAcesso::deEntrada($codigo), $ip, $agente);
    }

    public function pdf(string $codigo, ?int $versao = null): DownloadDoLaudo
    {
        return $this->acesso->pdf(CodigoDeAcesso::deEntrada($codigo), $versao);
    }
}
