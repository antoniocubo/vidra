<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vet;

use App\Http\Controllers\Controller;
use App\Http\Middleware\DefinirLaboratorioDoVeterinario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Shared\Domain\DomainException;
use Vidra\Veterinaria\Application\AcessoDoVeterinario\AceitarConviteDoVeterinario;
use Vidra\Veterinaria\Application\AcessoDoVeterinario\ConsultarConviteDoVeterinario;
use Vidra\Veterinaria\Application\AcessoDoVeterinario\ConvidarVeterinario;
use Vidra\Veterinaria\Domain\Vinculo\Exceptions\VinculoNaoEncontrado;

final class ConviteController extends Controller
{
    public function enviar(Request $request, string $vinculo, ConvidarVeterinario $convidar): RedirectResponse
    {
        try {
            $convidar(new \Vidra\Veterinaria\Application\AcessoDoVeterinario\ConvidarVeterinarioInput($vinculo, (int) $request->user()?->getAuthIdentifier()));
        } catch (VinculoNaoEncontrado) {
            abort(404);
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['acesso' => $e->getMessage()]);
        }

        return back()->with('success', 'Acesso enviado por e-mail. O convite vale por 7 dias.');
    }

    public function show(string $token, ConsultarConviteDoVeterinario $consultar): Response
    {
        try {
            $convite = $consultar($token);
        } catch (DomainException $e) {
            return Inertia::render('vet/convite', ['token' => $token, 'convite' => null, 'erro' => $e->getMessage()]);
        }

        return Inertia::render('vet/convite', ['token' => $token, 'convite' => $convite, 'erro' => null]);
    }

    public function store(Request $request, string $token, AceitarConviteDoVeterinario $aceitar): RedirectResponse
    {
        $dados = $request->validate(['senha' => ['required', 'string', 'min:10', 'confirmed']]);
        try {
            $resultado = $aceitar(new \Vidra\Veterinaria\Application\AcessoDoVeterinario\AceitarConviteDoVeterinarioInput($token, $dados['senha']));
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['form' => $e->getMessage()]);
        }
        Auth::guard('vet')->loginUsingId($resultado['contaId']);
        $request->session()->regenerate();
        $request->session()->put(DefinirLaboratorioDoVeterinario::SESSAO, $resultado['laboratorioId']);

        return to_route('vet.inicio');
    }
}
