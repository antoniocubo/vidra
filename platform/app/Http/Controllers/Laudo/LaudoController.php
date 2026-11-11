<?php

declare(strict_types=1);

namespace App\Http\Controllers\Laudo;

use Closure;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Laudos\Application\AbrirLaudoPublico;
use Vidra\Laudos\Domain\PedidoNaoEncontrado;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;

final class LaudoController
{
    public function acesso(): Response
    {
        return Inertia::render('laudo/acesso');
    }

    public function show(Request $request, string $codigo, AbrirLaudoPublico $abrir): Response|\Symfony\Component\HttpFoundation\Response
    {
        try {
            $c = $abrir($codigo, $request->ip() ?? '', $request->userAgent() ?? '');
        } catch (PedidoNaoEncontrado|InvalidValue) {
            return Inertia::render('laudo/acesso', ['erro' => 'Não encontramos um laudo com este código. Confira as letras e os números.'])->toResponse($request)->setStatusCode(404);
        }

        return Inertia::render('laudo/show', ['laudo' => [
            'versao' => $c->versao, 'retificacao' => $c->retificacao === null ? null : ['motivo' => $c->retificacao->motivo, 'em' => $c->retificacao->em->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y')],
            'versoes' => array_values(array_map(fn ($v): array => ['versao' => $v->versao, 'em' => $v->liberadoEm->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i'), 'pdf' => $v->pdfCaminho === null ? null : route('laudo.versao.pdf', ['codigo' => $codigo, 'versao' => $v->versao])], array_filter($c->versoes, fn ($v): bool => $v->versao < $c->versao))),
            'numero' => $c->numero, 'codigo' => $c->codigo, 'laboratorio' => $c->laboratorio, 'paciente' => $c->paciente,
            'datas' => $c->datas, 'exames' => $c->exames, 'textos' => $c->textos,
            'rtNome' => $c->rtNome, 'rtRegistro' => $c->rtRegistro, 'observacao' => $c->observacao,
            'ressalvas' => $c->ressalvas, 'avisos' => $c->avisos,
            'pdf' => $c->pdfPronto ? route('laudo.pdf', ['codigo' => str_replace('-', '', $c->codigo)]) : null,
            'whatsapp' => 'https://wa.me/?text='.rawurlencode('Laudo de '.$c->paciente['nome'].': '.$c->link.' · Código de acesso: '.$c->codigo),
        ]]);
    }

    public function pdf(Request $request, string $codigo, AbrirLaudoPublico $abrir, ?int $versao = null): HttpResponse
    {
        $this->encontrar(fn () => $abrir($codigo, $request->ip() ?? '', $request->userAgent() ?? ''));
        $pdf = $this->encontrar(fn () => $abrir->pdf($codigo, $versao));

        return response($pdf->bytes)->withHeaders(['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$pdf->nome.'"', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    /** @template T
     * @param  Closure():T  $acao
     * @return T
     */
    private function encontrar(Closure $acao): mixed
    {
        try {
            return $acao();
        } catch (PedidoNaoEncontrado|InvalidValue) {
            abort(404, 'Não encontramos um laudo com este código. Confira as letras e os números.');
        }
    }
}
