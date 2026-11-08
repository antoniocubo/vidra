<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\DefinirExameReferenciasRequest;
use App\Http\Requests\Lab\SalvarExameRequest;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Catalogo\Application\AlterarExame;
use Vidra\Catalogo\Application\CadastrarExame;
use Vidra\Catalogo\Application\DefinirFaixasDeReferencia;
use Vidra\Catalogo\Application\DesativarExame;
use Vidra\Catalogo\Application\DetalharExame;
use Vidra\Catalogo\Application\ListarExames;
use Vidra\Catalogo\Application\ReativarExame;
use Vidra\Catalogo\Domain\Exame\Exceptions\AnalitoRepetido;
use Vidra\Catalogo\Domain\Exame\Exceptions\CodigoDeExameJaUsado;
use Vidra\Catalogo\Domain\Exame\Exceptions\ErroNaFaixa;
use Vidra\Catalogo\Domain\Exame\Exceptions\ExameNaoEncontrado;
use Vidra\Catalogo\Domain\Exame\Exceptions\PerfilDesconhecido;
use Vidra\Catalogo\Domain\Exame\Setor;
use Vidra\Shared\Domain\DomainException;

final class ExameController extends Controller
{
    public function index(Request $request, ListarExames $listar, DetalharExame $detalhar): Response
    {
        $request->validate(['busca' => ['nullable', 'string', 'max:255'], 'setor' => ['nullable', Rule::enum(Setor::class)], 'inativos' => ['nullable', 'boolean'], 'exame' => ['nullable', 'uuid']]);
        $detalhe = $request->filled('exame') ? $this->executar(fn () => $detalhar($request->string('exame')->value())) : null;

        return Inertia::render('lab/exames/index', ['exames' => $listar($request->string('busca')->value(), $request->filled('setor') ? $request->string('setor')->value() : null, $request->boolean('inativos')), 'detalhe' => $detalhe, 'filtros' => ['busca' => $request->string('busca')->value(), 'setor' => $request->string('setor')->value(), 'inativos' => $request->boolean('inativos')]]);
    }

    public function create(): Response
    {
        return Inertia::render('lab/exames/create');
    }

    public function store(SalvarExameRequest $request, CadastrarExame $cadastrar): RedirectResponse
    {
        $id = $this->executar(fn () => $cadastrar($request->toInput()));

        return to_route('lab.exames.index', ['exame' => $id->value])->with('success', 'Exame cadastrado.');
    }

    public function edit(string $exame, DetalharExame $detalhar): Response
    {
        return Inertia::render('lab/exames/edit', $this->executar(fn () => $detalhar($exame)));
    }

    public function update(string $exame, SalvarExameRequest $request, AlterarExame $alterar): RedirectResponse
    {
        $this->executar(fn () => $alterar($exame, $request->toInput()));

        return to_route('lab.exames.index', ['exame' => $exame])->with('success', 'Exame atualizado.');
    }

    public function referencias(string $exame, DetalharExame $detalhar): Response
    {
        return Inertia::render('lab/exames/referencias', $this->executar(fn () => $detalhar($exame)));
    }

    public function salvarReferencias(string $exame, DefinirExameReferenciasRequest $request, DefinirFaixasDeReferencia $definir): RedirectResponse
    {
        $this->executar(fn () => $definir($exame, $request->toInput()));

        return back()->with('success', 'Referências salvas.');
    }

    public function desativar(string $exame, DesativarExame $desativar): RedirectResponse
    {
        $this->executar(fn () => $desativar($exame));

        return back()->with('success', 'Exame desativado.');
    }

    public function reativar(string $exame, ReativarExame $reativar): RedirectResponse
    {
        $this->executar(fn () => $reativar($exame));

        return back()->with('success', 'Exame reativado.');
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $acao
     * @return T
     */
    private function executar(Closure $acao): mixed
    {
        try {
            return $acao();
        } catch (ExameNaoEncontrado) {
            abort(404);
        } catch (CodigoDeExameJaUsado $e) {
            throw ValidationException::withMessages(['codigo' => $e->getMessage()]);
        } catch (AnalitoRepetido $e) {
            throw ValidationException::withMessages(['analitos' => $e->getMessage()]);
        } catch (ErroNaFaixa|PerfilDesconhecido $e) {
            throw ValidationException::withMessages([$e->campo => $e->getMessage()]);
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['form' => $e->getMessage()]);
        }
    }
}
