<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use App\Http\Requests\Lab\LiberarLaudoRequest;
use App\Http\Requests\Lab\RegistrarAvisoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Laudos\Application\FilaDeLiberacao;
use Vidra\Laudos\Application\LiberarLaudo;
use Vidra\Laudos\Application\ObterConferencia;
use Vidra\Laudos\Application\RegistrarAvisoDeValorCritico;
use Vidra\Laudos\Domain\ResponsavelTecnico;

final class LiberacaoController extends ResultadosController
{
    public function index(Request $request, FilaDeLiberacao $fila, ObterConferencia $obter, ResponsavelTecnico $rt, \Vidra\Laudos\Application\LaudoDoPedido $laudo): Response
    {
        $request->validate(['pedido' => ['nullable', 'uuid']]);
        $pedidos = $fila();
        $id = $request->string('pedido')->value() ?: ($pedidos[0]->id ?? null);
        $selecionado = null;
        if ($id !== null) {
            $p = $this->executar(fn () => $obter($id));
            $linhas = [];
            foreach ($p->resultados as $r) {
                $linhas[] = ['id' => $r->id, 'itemId' => $r->itemId, 'exame' => $r->exame, 'nome' => $r->nome, 'valor' => $r->valor, 'unidade' => $r->unidade, 'classificacao' => $r->classificacao, 'referencia' => $r->referencia, 'digitador' => $r->digitador, 'digitadoEm' => $this->data($r->digitadoEm, 'd/m/Y H:i'), 'anterior' => $r->anterior, 'anteriorEm' => $this->data($r->anteriorEm), 'delta' => $r->delta, 'percentual' => $r->percentual, 'avisado' => $r->avisado(), 'destinatario' => $r->aviso?->destinatario];
            }
            $atual = $laudo($id);
            $selecionado = ['retificacao' => $atual?->reabertoMotivo, 'proximaVersao' => ($atual->versao ?? 0) + 1, 'id' => $p->id, 'paciente' => $p->paciente, 'protocolo' => $p->protocolo, 'solicitante' => $p->solicitante, 'telefone' => $p->telefoneCritico, 'itens' => $p->itens, 'resultados' => $linhas, 'pronto' => $p->pronto()];
        }

        return Inertia::render('lab/liberacao/index', ['fila' => array_map(fn ($p): array => ['retificacao' => $laudo($p->id)?->reabertoMotivo, 'id' => $p->id, 'protocolo' => $p->protocolo, 'paciente' => $p->paciente, 'solicitante' => $p->solicitante, 'criticos' => count(array_filter($p->resultados, fn ($r): bool => $r->critico())), 'alterados' => count(array_filter($p->resultados, fn ($r): bool => in_array($r->classificacao, ['alto', 'baixo'], true)))], $pedidos), 'selecionado' => $selecionado, 'eRt' => $rt->ehRt((int) $request->user()?->getAuthIdentifier())]);
    }

    public function retificar(\App\Http\Requests\Lab\RetificarLaudoRequest $request, string $pedido, \Vidra\Laudos\Application\ReabrirLaudo $reabrir): RedirectResponse
    {
        $this->executar(fn () => $reabrir($request->toInput($pedido)));

        return back()->with('success', 'Laudo aberto para retificação.');
    }

    public function aviso(RegistrarAvisoRequest $request, string $pedido, RegistrarAvisoDeValorCritico $registrar): RedirectResponse
    {
        $this->executar(fn () => $registrar($request->toInput($pedido)));

        return back()->with('success', 'Aviso de valor crítico registrado.');
    }

    public function liberar(LiberarLaudoRequest $request, string $pedido, LiberarLaudo $liberar): RedirectResponse
    {
        $numero = $this->executar(fn () => $liberar($request->toInput($pedido)));

        return to_route('lab.pedidos.show', ['pedido' => $pedido])->with('success', "Laudo {$numero} liberado.");
    }
}
