<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\RegistrarVeterinarioRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Shared\Domain\ValueObjects\Uf;
use Vidra\Veterinaria\Application\RegistrarVeterinario\RegistrarVeterinario;
use Vidra\Veterinaria\Domain\Veterinario\Tratamento;
use Vidra\Veterinaria\Domain\Vinculo\Exceptions\VeterinarioJaVinculado;

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
            $resultado = $registrar($request->toInput());
        } catch (VeterinarioJaVinculado $e) {
            throw ValidationException::withMessages(['crmv_numero' => $e->getMessage()]);
        } catch (InvalidValue $e) {
            throw ValidationException::withMessages(['form' => $e->getMessage()]);
        }

        return to_route('lab.veterinarios.create')->with('success', $resultado->reaproveitado
            ? $resultado->nomeDeExibicao.' já estava no Vidra e agora faz parte do seu laboratório.'
            : 'Veterinário cadastrado.');
    }
}
