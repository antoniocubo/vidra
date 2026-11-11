<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

interface EntregaDoPedido
{
    /** @return array{pdfPronto:bool,pdfErro:?string,whatsapp:?string,link:string,entregas:list<string>}|null */
    public function painel(string $pedidoId): ?array;

    public function pdf(string $pedidoId, ?int $versao = null): DownloadDoLaudo;

    public function reenviar(string $pedidoId): void;
}
