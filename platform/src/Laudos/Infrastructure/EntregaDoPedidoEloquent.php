<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure;

use Vidra\Laudos\Application\MontarLaudo\FormatoDoLaudo;
use Vidra\Laudos\Application\MontarLaudo\MontarLaudo;
use Vidra\Laudos\Domain\ArquivoDoLaudo;
use Vidra\Laudos\Domain\DownloadDoLaudo;
use Vidra\Laudos\Domain\EntregaDoPedido;
use Vidra\Laudos\Domain\PedidoNaoEncontrado;
use Vidra\Laudos\Infrastructure\Jobs\EntregarLaudo;
use Vidra\Laudos\Infrastructure\Persistence\EntregaModel;
use Vidra\Laudos\Infrastructure\Persistence\LaudoModel;

final readonly class EntregaDoPedidoEloquent implements EntregaDoPedido
{
    public function __construct(private MontarLaudo $montar, private ArquivoDoLaudo $arquivos) {}

    public function painel(string $pedidoId): ?array
    {
        $m = LaudoModel::query()->where('pedido_id', $pedidoId)->first();
        if ($m === null) {
            return null;
        }
        $c = ($this->montar)($pedidoId);
        $entregas = [];
        foreach (EntregaModel::query()->where('laudo_id', $m->id)->orderByDesc('quando')->get() as $e) {
            $entregas[] = ($e->situacao === 'enviado' ? 'Enviado para ' : 'Falha no envio para ').$e->destinatario.' em '.FormatoDoLaudo::data($e->quando, 'd/m/Y').' às '.FormatoDoLaudo::data($e->quando, 'H:i');
        }
        if ($c->resumoDiario) {
            $entregas[] = 'Solicitante com resumo diário: envio não realizado. Resumo diário ainda não disponível.';
        }

        return ['pdfPronto' => $m->pdf_caminho !== null, 'pdfErro' => $m->pdf_erro, 'whatsapp' => $c->whatsapp, 'link' => $c->link, 'entregas' => $entregas];
    }

    public function pdf(string $pedidoId, ?int $versao = null): DownloadDoLaudo
    {
        $m = LaudoModel::query()->where('pedido_id', $pedidoId)->first();
        if ($m === null || ($versao === null && $m->pdf_caminho === null)) {
            throw new PedidoNaoEncontrado;
        }
        if ($versao !== null) {
            $v = Persistence\VersaoLaudoModel::query()->where('laudo_id', $m->id)->where('versao', $versao)->first();
            if ($v === null || $v->pdf_caminho === null) {
                throw new PedidoNaoEncontrado;
            }

            return new DownloadDoLaudo($this->arquivos->ler($v->pdf_caminho), 'laudo-'.$m->numero.'-v'.$versao.'.pdf');
        }
        $c = ($this->montar)($pedidoId);

        return new DownloadDoLaudo($this->arquivos->ler($m->pdf_caminho), $c->nomeArquivo());
    }

    public function reenviar(string $pedidoId): void
    {
        $m = LaudoModel::query()->where('pedido_id', $pedidoId)->first() ?? throw new PedidoNaoEncontrado;
        // Sem PDF (a geração falhou em todas as tentativas, ou o laudo é anterior à entrega
        // automática), o reenvio refaz a entrega completa em vez de deixar o laudo preso.
        EntregarLaudo::dispatch($m->laboratorio_id, $pedidoId, $m->pdf_caminho === null ? 'completa' : 'emails', $m->versao);
    }
}
