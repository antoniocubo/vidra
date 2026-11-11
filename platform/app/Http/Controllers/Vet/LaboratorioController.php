<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vet;

use App\Http\Controllers\Controller;
use App\Http\Middleware\DefinirLaboratorioDoVeterinario;
use App\Models\ContaVeterinario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Vidra\Veterinaria\Application\LaboratoriosDoVeterinario\LaboratoriosDoVeterinario;
use Vidra\Veterinaria\Domain\Acesso\LaboratorioDoVeterinario;

/** Troca do laboratório no acesso vet; a tela de escolha (board 7a) é do Vet\PedidoController. */
final class LaboratorioController extends Controller
{
    public function __construct(private readonly LaboratoriosDoVeterinario $laboratorios) {}

    public function store(Request $request): RedirectResponse
    {
        $id = $request->validate(['laboratorio_id' => ['required', 'string']])['laboratorio_id'];
        $permitidos = array_map(static fn (LaboratorioDoVeterinario $l): string => $l->laboratorioId, $this->doVeterinario($request));

        if (! in_array($id, $permitidos, true)) {
            throw ValidationException::withMessages(['laboratorio_id' => 'Você não tem acesso a este laboratório.']);
        }

        $request->session()->put(DefinirLaboratorioDoVeterinario::SESSAO, $id);

        return to_route('vet.inicio');
    }

    /** @return list<LaboratorioDoVeterinario> */
    private function doVeterinario(Request $request): array
    {
        $conta = $request->user('vet');
        abort_unless($conta instanceof ContaVeterinario, 401);

        return ($this->laboratorios)($conta->veterinario_id);
    }
}
