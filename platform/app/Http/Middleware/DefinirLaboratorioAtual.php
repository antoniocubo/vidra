<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Vidra\Acesso\Application\LaboratoriosDoUsuario\LaboratoriosDoUsuario;
use Vidra\Acesso\Domain\LaboratorioDoUsuario;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;

/**
 * Define em qual laboratório a requisição trabalha. O escolhido fica na sessão,
 * mas o vínculo é conferido de novo a cada requisição: quem perdeu o acesso sai na hora.
 */
final class DefinirLaboratorioAtual
{
    public const string SESSAO = 'laboratorio_id';

    public function __construct(
        private readonly LaboratoriosDoUsuario $laboratoriosDoUsuario,
        private readonly ContextoLaboratorio $contexto,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        abort_if($usuario === null, 401);

        $laboratorios = ($this->laboratoriosDoUsuario)((int) $usuario->getAuthIdentifier());
        $escolhido = $this->escolhido($request, $laboratorios);

        if ($escolhido === null) {
            abort_if($laboratorios === [], 403, 'Sua conta não está ligada a nenhum laboratório.');

            return redirect()->guest(route('laboratorio.escolher'));
        }

        $request->session()->put(self::SESSAO, $escolhido->id->value);
        $request->attributes->set('laboratorio', $escolhido);
        $request->attributes->set('laboratorios', $laboratorios);
        $this->contexto->definir($escolhido->id->value);

        try {
            return $next($request);
        } finally {
            $this->contexto->limpar();
        }
    }

    /** @param list<LaboratorioDoUsuario> $laboratorios */
    private function escolhido(Request $request, array $laboratorios): ?LaboratorioDoUsuario
    {
        $naSessao = $request->session()->get(self::SESSAO);

        foreach ($laboratorios as $laboratorio) {
            if ($laboratorio->id->value === $naSessao) {
                return $laboratorio;
            }
        }

        return count($laboratorios) === 1 ? $laboratorios[0] : null;
    }
}
