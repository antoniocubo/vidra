<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vet;

use App\Http\Controllers\Controller;
use App\Models\ContaVeterinario;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Vidra\Shared\Domain\DomainException;
use Vidra\Veterinaria\Application\LaboratoriosDoVeterinario\LaboratoriosDoVeterinario;
use Vidra\Veterinaria\Application\PedidosDoVeterinario\BaixarLaudoDoVeterinario;
use Vidra\Veterinaria\Application\PedidosDoVeterinario\PedidosDoVeterinario;
use Vidra\Veterinaria\Application\PedidosDoVeterinario\PedidosDoVeterinarioInput;
use Vidra\Veterinaria\Domain\Acesso\DadosDoAcesso;
use Vidra\Veterinaria\Domain\Acesso\LaboratorioDoVeterinario;

final class PedidoController extends Controller
{
    public function escolher(Request $request, LaboratoriosDoVeterinario $laboratorios, DadosDoAcesso $dados): Response
    {
        $conta = $request->user('vet');
        abort_unless($conta instanceof ContaVeterinario, 401);
        $labs = $laboratorios($conta->veterinario_id);
        $novidades = [];
        foreach ($labs as $lab) {
            $novidades[$lab->laboratorioId] = $dados->novidades($lab);
        }

        return Inertia::render('vet/escolher-laboratorio', [
            'laboratorios' => array_map(fn (LaboratorioDoVeterinario $l): array => ['id' => $l->laboratorioId, 'nome' => $l->nome], $labs),
            'novidades' => $novidades, 'identidade' => $dados->identidade($conta->veterinario_id),
        ]);
    }

    public function index(Request $request, PedidosDoVeterinario $listar, DadosDoAcesso $dados): Response
    {
        $request->validate(['busca' => ['nullable', 'string', 'max:255'], 'pagina' => ['nullable', 'integer', 'min:1']]);
        $acesso = $this->acesso($request);
        $conta = $request->user('vet');
        abort_unless($conta instanceof ContaVeterinario, 401);
        $pagina = $listar($acesso, new PedidosDoVeterinarioInput($request->string('busca')->value(), $request->integer('pagina', 1)));
        $dados->registrarAcesso($acesso);

        return Inertia::render('vet/inicio', ['lista' => $pagina, 'busca' => $request->string('busca')->value(), 'laboratorio' => $acesso, 'laboratorios' => $request->attributes->get('vet_laboratorios'), 'identidade' => $dados->identidade($conta->veterinario_id)]);
    }

    public function pdf(Request $request, string $pedido, BaixarLaudoDoVeterinario $baixar): HttpResponse
    {
        try {
            $pdf = $baixar($this->acesso($request), $pedido, $request->ip() ?? '', $request->userAgent() ?? '');
        } catch (DomainException|\Vidra\Laudos\Domain\PedidoNaoEncontrado) {
            abort(404);
        }

        return response($pdf['conteudo'], 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$pdf['nome'].'"', 'Cache-Control' => 'private, no-store', 'Referrer-Policy' => 'no-referrer']);
    }

    private function acesso(Request $request): LaboratorioDoVeterinario
    {
        $acesso = $request->attributes->get('vet_laboratorio');
        abort_unless($acesso instanceof LaboratorioDoVeterinario, 404);

        return $acesso;
    }
}
