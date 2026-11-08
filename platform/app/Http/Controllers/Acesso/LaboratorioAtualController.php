<?php

declare(strict_types=1);

namespace App\Http\Controllers\Acesso;

use App\Http\Controllers\Controller;
use App\Http\Middleware\DefinirLaboratorioAtual;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Acesso\Application\LaboratoriosDoUsuario\LaboratoriosDoUsuario;
use Vidra\Acesso\Domain\LaboratorioDoUsuario;

/** Escolha e troca do laboratório em que o usuário está trabalhando. */
final class LaboratorioAtualController extends Controller
{
    public function create(Request $request, LaboratoriosDoUsuario $laboratoriosDoUsuario): Response|RedirectResponse
    {
        $laboratorios = $laboratoriosDoUsuario($this->usuarioId($request));
        if (count($laboratorios) === 1) {
            return $this->entrarEm($request, $laboratorios[0]->id->value);
        }

        return Inertia::render('acesso/escolher-laboratorio', [
            'laboratorios' => array_map(static fn (LaboratorioDoUsuario $laboratorio): array => [
                'id' => $laboratorio->id->value, 'nome' => $laboratorio->nome, 'papel' => $laboratorio->papel,
            ], $laboratorios),
        ]);
    }

    public function store(Request $request, LaboratoriosDoUsuario $laboratoriosDoUsuario): RedirectResponse
    {
        $id = $request->string('laboratorio_id')->toString();
        $permitidos = array_map(static fn (LaboratorioDoUsuario $laboratorio): string => $laboratorio->id->value, $laboratoriosDoUsuario($this->usuarioId($request)));

        if (! in_array($id, $permitidos, true)) {
            throw ValidationException::withMessages(['laboratorio_id' => 'Você não tem acesso a este laboratório.']);
        }

        return $this->entrarEm($request, $id);
    }

    private function entrarEm(Request $request, string $laboratorioId): RedirectResponse
    {
        $request->session()->put(DefinirLaboratorioAtual::SESSAO, $laboratorioId);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function usuarioId(Request $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }
}
