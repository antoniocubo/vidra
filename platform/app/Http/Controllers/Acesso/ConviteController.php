<?php

declare(strict_types=1);

namespace App\Http\Controllers\Acesso;

use App\Http\Controllers\Controller;
use App\Http\Middleware\DefinirLaboratorioAtual;
use App\Http\Requests\Acesso\AceitarConviteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Acesso\Application\AceitarConvite;
use Vidra\Acesso\Application\ConsultarConvite;
use Vidra\Acesso\Domain\ConviteInvalido;
use Vidra\Shared\Domain\DomainException;

final class ConviteController extends Controller
{
    public function show(Request $request, string $token, ConsultarConvite $consultar): Response
    {
        try {
            $convite = $consultar($token);
        } catch (ConviteInvalido $e) {
            return $this->erro($token, $e->getMessage());
        }
        if ($request->user() === null) {
            $request->session()->put('url.intended', route('convite.show', ['token' => $token]));
        }

        return Inertia::render('acesso/convite', ['token' => $token, 'convite' => $convite, 'erro' => null]);
    }

    public function store(AceitarConviteRequest $request, string $token, AceitarConvite $aceitar): Response|RedirectResponse
    {
        try {
            $resultado = $aceitar($request->toInput($token));
        } catch (ConviteInvalido $e) {
            return $this->erro($token, $e->getMessage());
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['form' => $e->getMessage()]);
        }
        Auth::loginUsingId($resultado->usuarioId);
        $request->session()->regenerate();
        $request->session()->forget('url.intended');
        $request->session()->put(DefinirLaboratorioAtual::SESSAO, $resultado->laboratorioId);

        return to_route('dashboard');
    }

    private function erro(string $token, string $mensagem): Response
    {
        return Inertia::render('acesso/convite', ['token' => $token, 'convite' => null, 'erro' => $mensagem]);
    }
}
