<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use App\Http\Requests\Lab\DigitarResultadosRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Bancada\Application\AbrirItemNaBancada;
use Vidra\Bancada\Application\DigitarResultados;
use Vidra\Bancada\Application\EnviarParaConferencia;
use Vidra\Bancada\Application\MapaDeTrabalho;
use Vidra\Bancada\Application\ReabrirItem;

final class BancadaController extends ResultadosController
{
    public function index(Request $request, MapaDeTrabalho $mapa, AbrirItemNaBancada $abrir): Response
    {
        $request->validate(['setor' => ['nullable', 'string', 'max:40'], 'item' => ['nullable', 'uuid']]);
        $setor = $request->string('setor')->value() ?: $this->primeiroSetorComTrabalho($mapa(null)['setores']);
        $lista = $mapa($setor);
        $id = $request->string('item')->value() ?: ($lista['itens'][0]->id ?? null);
        $selecionado = null;
        if ($id !== null) {
            $aberto = $this->executar(fn () => $abrir($id));
            $linhas = [];
            foreach ($aberto['analitos'] as $linha) {
                $a = $linha['analito'];
                $r = $linha['resultado'];
                $anterior = $linha['anterior'];
                $f = $r !== null && $aberto['item']->situacao !== 'em_analise' ? $r->faixa : $a->faixa;
                $linhas[] = ['id' => $a->id, 'nome' => $a->nome, 'unidade' => $a->unidade, 'casas' => $a->casasDecimais, 'faixa' => $f === null ? null : ['perfil' => $f->perfil, 'minimo' => $f->minimo?->value, 'maximo' => $f->maximo?->value, 'criticoBaixo' => $f->criticoBaixo?->value, 'criticoAlto' => $f->criticoAlto?->value], 'referencia' => $f?->formatada() ?? 'Sem referência', 'valor' => $r?->valor?->value, 'texto' => $r?->texto, 'classificacao' => $r?->classificacao->value, 'anterior' => $anterior === null ? null : ['valor' => $anterior->valor?->value, 'texto' => $anterior->texto, 'data' => $this->data($anterior->liberadoEm)]];
            }
            $selecionado = ['item' => $aberto['item'], 'perfilDescricao' => $aberto['perfilDescricao'], 'analitos' => $linhas];
        }

        return Inertia::render('lab/bancada/index', ['mapa' => $lista['itens'], 'setores' => $lista['setores'], 'setor' => $setor, 'selecionado' => $selecionado]);
    }

    public function salvar(DigitarResultadosRequest $request, string $item, DigitarResultados $digitar): RedirectResponse
    {
        $this->executar(fn () => $digitar($request->toInput($item)));

        return back()->with('success', 'Resultados salvos.');
    }

    public function conferir(string $item, EnviarParaConferencia $enviar): RedirectResponse
    {
        $this->executar(fn () => $enviar($item));

        return to_route('lab.bancada.index')->with('success', 'Resultados enviados para conferência.');
    }

    public function reabrir(string $item, ReabrirItem $reabrir): RedirectResponse
    {
        $this->executar(fn () => $reabrir($item));

        return to_route('lab.bancada.index', ['item' => $item])->with('success', 'Item reaberto para correção.');
    }

    /**
     * Sem setor na URL (vindo do painel, por exemplo), abre o primeiro que tem itens.
     *
     * @param  array<string, int>  $setores
     */
    private function primeiroSetorComTrabalho(array $setores): string
    {
        foreach ($setores as $setor => $quantidade) {
            if ($quantidade > 0) {
                return $setor;
            }
        }

        return 'hematologia';
    }
}
