<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\AlterarTutorRequest;
use App\Http\Requests\Lab\CadastrarTutorRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Catalogo\Domain\Referencia\PerfisDeReferencia;
use Vidra\Shared\Domain\DomainException;
use Vidra\Veterinaria\Application\AlterarTutor\AlterarTutor;
use Vidra\Veterinaria\Application\AutorizarLaudoPorEmail\AutorizarLaudoPorEmail;
use Vidra\Veterinaria\Application\AutorizarLaudoPorEmail\AutorizarLaudoPorEmailInput;
use Vidra\Veterinaria\Application\BuscarTutores\BuscarTutores;
use Vidra\Veterinaria\Application\BuscarTutores\BuscarTutoresInput;
use Vidra\Veterinaria\Application\CadastrarTutor\CadastrarTutor;
use Vidra\Veterinaria\Application\DetalharTutor\DetalharTutor;
use Vidra\Veterinaria\Application\DetalharTutor\DetalharTutorInput;
use Vidra\Veterinaria\Application\ListarVinculosParaEscolha\ListarVinculosParaEscolha;
use Vidra\Veterinaria\Application\RetirarAutorizacaoDeEmail\RetirarAutorizacaoDeEmail;
use Vidra\Veterinaria\Application\RetirarAutorizacaoDeEmail\RetirarAutorizacaoDeEmailInput;

final class TutorController extends Controller
{
    public function index(Request $request, BuscarTutores $buscar, DetalharTutor $detalhar): Response
    {
        $request->validate(['busca' => ['nullable', 'string', 'max:255'], 'tutor' => ['nullable', 'uuid']]);
        $tutores = $buscar(new BuscarTutoresInput($request->string('busca')->value()));
        $id = $request->filled('tutor') ? $request->string('tutor')->value() : ($tutores[0]->id ?? null);
        try {
            $selecionado = $id === null ? null : $detalhar(new DetalharTutorInput($id));
        } catch (DomainException $e) {
            TutorErrosController::traduzir($e);
        }

        return Inertia::render('lab/tutores/index', ['tutores' => $tutores, 'selecionado' => $selecionado, 'busca' => $request->string('busca')->value()]);
    }

    public function create(Request $request, ListarVinculosParaEscolha $listar, PerfisDeReferencia $perfis): Response
    {
        return Inertia::render('lab/tutores/create', ['celular' => $request->string('celular')->value(), 'vinculos' => $listar(), 'perfis' => $perfis->todos(), 'hoje' => now('America/Sao_Paulo')->format('Y-m-d')]);
    }

    public function store(CadastrarTutorRequest $request, CadastrarTutor $cadastrar): RedirectResponse
    {
        try {
            $id = $cadastrar($request->toInput());
        } catch (DomainException $e) {
            TutorErrosController::traduzir($e, 'animais.0.');
        }

        return to_route('lab.tutores.index', ['tutor' => $id->value])->with('success', $request->string('nome')->trim()->value().' cadastrada.');
    }

    public function edit(string $tutor, DetalharTutor $detalhar): Response
    {
        try {
            $dto = $detalhar(new DetalharTutorInput($tutor));
        } catch (DomainException $e) {
            TutorErrosController::traduzir($e);
        }

        return Inertia::render('lab/tutores/edit', ['tutor' => $dto]);
    }

    public function update(AlterarTutorRequest $request, AlterarTutor $alterar): RedirectResponse
    {
        try {
            $id = $alterar($request->toInput());
        } catch (DomainException $e) {
            TutorErrosController::traduzir($e);
        }

        return to_route('lab.tutores.index', ['tutor' => $id->value])->with('success', 'Contato do tutor atualizado.');
    }

    public function autorizar(Request $request, string $tutor, AutorizarLaudoPorEmail $autorizar): RedirectResponse
    {
        try {
            $autorizar(new AutorizarLaudoPorEmailInput($tutor, (int) $request->user()?->getAuthIdentifier()));
        } catch (DomainException $e) {
            TutorErrosController::traduzir($e);
        }

        return to_route('lab.tutores.index', ['tutor' => $tutor])->with('success', 'Autorização de e-mail registrada.');
    }

    public function retirar(Request $request, string $tutor, RetirarAutorizacaoDeEmail $retirar): RedirectResponse
    {
        try {
            $retirar(new RetirarAutorizacaoDeEmailInput($tutor, (int) $request->user()?->getAuthIdentifier()));
        } catch (DomainException $e) {
            TutorErrosController::traduzir($e);
        }

        return to_route('lab.tutores.index', ['tutor' => $tutor])->with('success', 'Autorização de e-mail retirada.');
    }
}
