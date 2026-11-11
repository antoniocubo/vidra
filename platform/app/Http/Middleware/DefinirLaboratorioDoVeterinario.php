<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ContaVeterinario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;
use Vidra\Veterinaria\Application\LaboratoriosDoVeterinario\LaboratoriosDoVeterinario;
use Vidra\Veterinaria\Domain\Acesso\LaboratorioDoVeterinario;

/**
 * O "laboratório atual" do acesso vet. Mesmo desenho do DefinirLaboratorioAtual:
 * o escolhido fica na sessão, mas o vínculo é conferido a cada requisição, e só
 * um laboratório por vez (o veterinário nunca vê laudos de laboratórios misturados).
 */
final class DefinirLaboratorioDoVeterinario
{
    public const string SESSAO = 'vet_laboratorio_id';

    public function __construct(
        private readonly LaboratoriosDoVeterinario $laboratoriosDoVeterinario,
        private readonly ContextoLaboratorio $contexto,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $conta = $request->user('vet');
        abort_unless($conta instanceof ContaVeterinario, 401);

        $laboratorios = ($this->laboratoriosDoVeterinario)($conta->veterinario_id);
        $escolhido = $this->escolhido($request, $laboratorios);

        if ($escolhido === null) {
            abort_if($laboratorios === [], 403, 'Nenhum laboratório liberou seu acesso ainda.');

            return redirect()->guest(route('vet.laboratorio.escolher'));
        }

        $request->session()->put(self::SESSAO, $escolhido->laboratorioId);
        $request->attributes->set('vet_laboratorio', $escolhido);
        $request->attributes->set('vet_laboratorios', $laboratorios);
        $this->contexto->definir($escolhido->laboratorioId);

        try {
            return $next($request);
        } finally {
            $this->contexto->limpar();
        }
    }

    /** @param list<LaboratorioDoVeterinario> $laboratorios */
    private function escolhido(Request $request, array $laboratorios): ?LaboratorioDoVeterinario
    {
        $naSessao = $request->session()->get(self::SESSAO);

        foreach ($laboratorios as $laboratorio) {
            if ($laboratorio->laboratorioId === $naSessao) {
                return $laboratorio;
            }
        }

        return count($laboratorios) === 1 ? $laboratorios[0] : null;
    }
}
