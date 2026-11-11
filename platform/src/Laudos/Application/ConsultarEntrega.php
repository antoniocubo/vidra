<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application;

use Vidra\Auditoria\Application\RegistrarNaTrilha;
use Vidra\Laudos\Domain\DownloadDoLaudo;
use Vidra\Laudos\Domain\EntregaDoPedido;

final readonly class ConsultarEntrega
{
    public function __construct(private EntregaDoPedido $entrega, private RegistrarNaTrilha $trilha, private \Vidra\Laudos\Domain\LaudoRepository $laudos) {}

    /** @return array{pdfPronto:bool,pdfErro:?string,whatsapp:?string,link:string,entregas:list<string>}|null */
    public function __invoke(string $pedidoId): ?array
    {
        return $this->entrega->painel($pedidoId);
    }

    public function pdf(string $pedidoId, ?int $versao = null): DownloadDoLaudo
    {
        $pdf = $this->entrega->pdf($pedidoId, $versao);
        ($this->trilha)('laudo_baixado', 'laudo', ($this->laudos->doPedido($pedidoId) ?? throw new \Vidra\Laudos\Domain\PedidoNaoEncontrado)->id, 'Baixou o laudo'.($versao === null ? '' : ' · versão '.$versao), $pedidoId);

        return $pdf;
    }

    public function reenviar(string $pedidoId): void
    {
        $this->entrega->reenviar($pedidoId);
        ($this->trilha)('laudo_reenviado', 'laudo', ($this->laudos->doPedido($pedidoId) ?? throw new \Vidra\Laudos\Domain\PedidoNaoEncontrado)->id, 'Reenviou o laudo', $pedidoId);
    }
}
