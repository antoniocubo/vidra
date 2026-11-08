<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\SalvarClinicaRequest;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Shared\Domain\DomainException;
use Vidra\Veterinaria\Application\AlterarClinica\AlterarClinica;
use Vidra\Veterinaria\Application\CadastrarClinica\CadastrarClinica;
use Vidra\Veterinaria\Application\DesativarClinica\DesativarClinica;
use Vidra\Veterinaria\Application\DetalharClinica\DetalharClinica;
use Vidra\Veterinaria\Application\ListarClinicas\ListarClinicas;
use Vidra\Veterinaria\Application\ListarClinicas\ListarClinicasInput;
use Vidra\Veterinaria\Application\ReativarClinica\ReativarClinica;
use Vidra\Veterinaria\Domain\Clinica\Exceptions\ClinicaNaoEncontrada;
use Vidra\Veterinaria\Domain\Clinica\Exceptions\CnpjDeClinicaJaCadastrado;
use Vidra\Veterinaria\Domain\Clinica\FormaDePagamento;

/** Adaptador HTTP do cadastro de clínicas parceiras. */
final class ClinicaController extends Controller
{
    public function index(Request $request, ListarClinicas $listar, DetalharClinica $detalhar): Response
    {
        $request->validate(['busca' => ['nullable', 'string', 'max:255'], 'inativas' => ['nullable', 'boolean'], 'clinica' => ['nullable', 'uuid']]);
        $filtros = ['busca' => $request->string('busca')->value(), 'inativas' => $request->boolean('inativas')];
        $clinicas = $listar(new ListarClinicasInput($filtros['busca'], $filtros['inativas']));
        $id = $request->filled('clinica') ? $request->string('clinica')->value() : ($clinicas[0]->id ?? null);

        return Inertia::render('lab/clinicas/index', [
            'clinicas' => $clinicas,
            'selecionado' => $id === null ? null : $this->executar(fn () => $detalhar($id)),
            'filtros' => $filtros,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('lab/clinicas/create', ['formasDePagamento' => array_column(FormaDePagamento::cases(), 'value')]);
    }

    public function store(SalvarClinicaRequest $request, CadastrarClinica $cadastrar): RedirectResponse
    {
        $id = $this->executar(fn () => $cadastrar($request->toInput()));

        return to_route('lab.clinicas.index', ['clinica' => $id->value])->with('success', 'Clínica cadastrada.');
    }

    public function edit(string $clinica, DetalharClinica $detalhar): Response
    {
        return Inertia::render('lab/clinicas/edit', [
            'clinica' => $this->executar(fn () => $detalhar($clinica)),
            'formasDePagamento' => array_column(FormaDePagamento::cases(), 'value'),
        ]);
    }

    public function update(string $clinica, SalvarClinicaRequest $request, AlterarClinica $alterar): RedirectResponse
    {
        $this->executar(fn () => $alterar($clinica, $request->toInput()));

        return to_route('lab.clinicas.index', ['clinica' => $clinica])->with('success', 'Clínica atualizada.');
    }

    public function desativar(string $clinica, DesativarClinica $desativar): RedirectResponse
    {
        $this->executar(fn () => $desativar($clinica));

        return to_route('lab.clinicas.index', ['clinica' => $clinica, 'inativas' => 1])->with('success', 'Clínica desativada.');
    }

    public function reativar(string $clinica, ReativarClinica $reativar): RedirectResponse
    {
        $this->executar(fn () => $reativar($clinica));

        return to_route('lab.clinicas.index', ['clinica' => $clinica])->with('success', 'Clínica reativada.');
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
        } catch (ClinicaNaoEncontrada) {
            abort(404);
        } catch (CnpjDeClinicaJaCadastrado $e) {
            throw ValidationException::withMessages(['cnpj' => $e->getMessage()]);
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['form' => $e->getMessage()]);
        }
    }
}
