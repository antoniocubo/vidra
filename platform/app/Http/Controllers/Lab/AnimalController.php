<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\AdicionarAnimalRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Catalogo\Domain\Referencia\PerfisDeReferencia;
use Vidra\Shared\Domain\DomainException;
use Vidra\Veterinaria\Application\AdicionarAnimal\AdicionarAnimal;
use Vidra\Veterinaria\Application\DetalharTutor\DetalharTutor;
use Vidra\Veterinaria\Application\DetalharTutor\DetalharTutorInput;
use Vidra\Veterinaria\Application\ListarVinculosParaEscolha\ListarVinculosParaEscolha;

final class AnimalController extends Controller
{
    public function create(string $tutor, DetalharTutor $detalhar, ListarVinculosParaEscolha $listar, PerfisDeReferencia $perfis): Response
    {
        try {
            $dto = $detalhar(new DetalharTutorInput($tutor));
        } catch (DomainException $e) {
            TutorErrosController::traduzir($e);
        }

        return Inertia::render('lab/tutores/animais/create', ['tutor' => $dto, 'vinculos' => $listar(), 'perfis' => $perfis->todos(), 'hoje' => now('America/Sao_Paulo')->format('Y-m-d')]);
    }

    public function store(AdicionarAnimalRequest $request, AdicionarAnimal $adicionar): RedirectResponse
    {
        try {
            $adicionar($request->toInput());
        } catch (DomainException $e) {
            TutorErrosController::traduzir($e);
        }

        return to_route('lab.tutores.index', ['tutor' => $request->route('tutor')])->with('success', 'Animal cadastrado.');
    }
}
