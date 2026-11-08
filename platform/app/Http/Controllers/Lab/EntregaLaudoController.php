<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Vidra\Laudos\Application\ConsultarEntrega;

final class EntregaLaudoController extends ResultadosController
{
    public function pdf(string $pedido, ConsultarEntrega $entrega): Response
    {
        $pdf = $this->executar(fn () => $entrega->pdf($pedido));

        return response($pdf->bytes)->withHeaders(['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$pdf->nome.'"', 'Cache-Control' => 'private, no-store']);
    }

    public function reenviar(string $pedido, ConsultarEntrega $entrega): RedirectResponse
    {
        $this->executar(fn () => $entrega->reenviar($pedido));

        return back()->with('success', 'Reenvio do laudo por e-mail colocado na fila.');
    }
}
