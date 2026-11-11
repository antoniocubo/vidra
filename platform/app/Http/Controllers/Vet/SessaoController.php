<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vet;

use App\Http\Controllers\Controller;
use App\Http\Middleware\DefinirLaboratorioDoVeterinario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Login e saída do acesso do veterinário (guard 'vet'). */
final class SessaoController extends Controller
{
    private const int TENTATIVAS = 5;

    public function create(Request $request): Response
    {
        return Inertia::render('vet/entrar', ['status' => $request->session()->get('status')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'email' => ['required', 'string', 'email'],
            'senha' => ['required', 'string'],
            'lembrar' => ['boolean'],
        ]);

        $chave = 'vet-login:'.Str::transliterate(Str::lower($dados['email'])).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($chave, self::TENTATIVAS)) {
            throw ValidationException::withMessages(['email' => 'Muitas tentativas. Tente de novo em '.RateLimiter::availableIn($chave).' segundos.']);
        }

        if (! Auth::guard('vet')->attempt(['email' => Str::lower($dados['email']), 'password' => $dados['senha']], (bool) ($dados['lembrar'] ?? false))) {
            RateLimiter::hit($chave);

            throw ValidationException::withMessages(['email' => 'E-mail ou senha não conferem.']);
        }

        RateLimiter::clear($chave);
        $request->session()->regenerate();
        $request->session()->forget(DefinirLaboratorioDoVeterinario::SESSAO);

        return redirect()->intended(route('vet.inicio'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('vet')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('vet.entrar');
    }
}
