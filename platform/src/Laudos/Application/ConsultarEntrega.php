<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application;

use Vidra\Laudos\Domain\DownloadDoLaudo;
use Vidra\Laudos\Domain\EntregaDoPedido;

final readonly class ConsultarEntrega
{
    public function __construct(private EntregaDoPedido $entrega) {}

    /** @return array{pdfPronto:bool,pdfErro:?string,whatsapp:?string,link:string,entregas:list<string>}|null */
    public function __invoke(string $pedidoId): ?array
    {
        return $this->entrega->painel($pedidoId);
    }

    public function pdf(string $pedidoId): DownloadDoLaudo
    {
        return $this->entrega->pdf($pedidoId);
    }

    public function reenviar(string $pedidoId): void
    {
        $this->entrega->reenviar($pedidoId);
    }
}
