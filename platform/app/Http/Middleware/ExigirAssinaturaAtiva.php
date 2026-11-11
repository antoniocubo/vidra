<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Vidra\Acesso\Application\Assinatura\SituacaoDaAssinaturaAtual;
use Vidra\Acesso\Domain\LaboratorioDoUsuario;
use Vidra\Acesso\Domain\Papel;

/**
 * Só consulta: o laboratório vê e baixa tudo, mas não abre pedido novo.
 * Roda depois do middleware 'laboratorio' e vai só nas rotas que abrem pedido.
 */
final class ExigirAssinaturaAtiva
{
    public function __construct(private readonly SituacaoDaAssinaturaAtual $situacao) {}

    public function handle(Request $request, Closure $next): Response
    {
        $laboratorio = $request->attributes->get('laboratorio');
        if (! $laboratorio instanceof LaboratorioDoUsuario || self::situacao($request, $this->situacao)['abrePedido']) {
            return $next($request);
        }

        $admin = Papel::tryFrom($laboratorio->papel)?->pode('assinatura') === true;

        return $admin
            ? to_route('lab.assinatura.index')->with('success', 'Seu teste terminou. Escolha um plano para voltar a abrir pedidos.')
            : to_route('lab.pedidos.index')->with('success', 'O laboratório está só em consulta. Fale com o administrador para escolher um plano.');
    }

    /**
     * Calcula uma vez por requisição: a faixa de aviso (HandleInertiaRequests) lê o mesmo resultado.
     *
     * @return array{situacao: string, abrePedido: bool, diasRestantes: int, deveAvisar: bool}
     */
    public static function situacao(Request $request, SituacaoDaAssinaturaAtual $situacao): array
    {
        $laboratorio = $request->attributes->get('laboratorio');
        assert($laboratorio instanceof LaboratorioDoUsuario);

        return $request->attributes->get('assinatura') ?? tap($situacao($laboratorio->id->value), static fn (array $dados) => $request->attributes->set('assinatura', $dados));
    }
}
