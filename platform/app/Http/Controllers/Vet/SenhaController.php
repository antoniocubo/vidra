<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vet;

use App\Http\Controllers\Controller;
use App\Models\ContaVeterinario;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class SenhaController extends Controller
{
    public function esqueci(Request $request): Response
    {
        return Inertia::render('vet/esqueci-senha', ['status' => $request->session()->get('status')]);
    }

    public function enviar(Request $request): RedirectResponse
    {
        $dados = $request->validate(['email' => ['required', 'email']]);
        Password::broker('contas_veterinario')->sendResetLink(['email' => Str::lower(trim($dados['email']))]);

        return back()->with('status', 'Se este e-mail tem acesso ao Vidra, você receberá um link para criar uma nova senha.');
    }

    public function nova(Request $request, string $token): Response
    {
        return Inertia::render('vet/nova-senha', ['token' => $token, 'email' => $request->string('email')->value()]);
    }

    public function salvar(Request $request, string $token): RedirectResponse
    {
        $dados = $request->validate(['email' => ['required', 'email'], 'senha' => ['required', 'string', 'min:10', 'confirmed']]);
        $status = Password::broker('contas_veterinario')->reset(['email' => Str::lower(trim($dados['email'])), 'password' => $dados['senha'], 'password_confirmation' => $dados['senha'], 'token' => $token], function (ContaVeterinario $conta, string $senha): void {
            $conta->forceFill(['password' => Hash::make($senha), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($conta));
        });
        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => 'Este link venceu ou não é válido. Peça um novo link para criar sua senha.']);
        }

        return to_route('vet.entrar')->with('status', 'Senha atualizada. Entre com sua nova senha.');
    }
}
