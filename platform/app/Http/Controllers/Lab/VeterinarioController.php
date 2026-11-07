<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\RegistrarVeterinarioRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Cadastros\Application\RegistrarVeterinario\RegistrarVeterinario;
use Vidra\Cadastros\Domain\Veterinario\Exceptions\CrmvJaCadastrado;
use Vidra\Cadastros\Domain\Veterinario\Tratamento;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Shared\Domain\ValueObjects\Uf;

/**
 * Adaptador HTTP: traduz a requisição para o caso de uso e o resultado para Inertia.
 */
final class VeterinarioController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('lab/veterinarios/create', [
            'tratamentos' => array_column(Tratamento::cases(), 'value'),
            'ufs' => array_column(Uf::cases(), 'value'),
        ]);
    }

    public function store(RegistrarVeterinarioRequest $request, RegistrarVeterinario $registrar): RedirectResponse
    {
        try {
            $registrar($request->toInput());
        } catch (CrmvJaCadastrado $e) {
            throw ValidationException::withMessages(['crmv_numero' => $e->getMessage()]);
        } catch (InvalidValue $e) {
            throw ValidationException::withMessages(['form' => $e->getMessage()]);
        }

        return to_route('lab.veterinarios.create')->with('success', 'Veterinário cadastrado.');
    }
}
