<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Vidra\Acesso\Domain\LaboratorioDoUsuario;
use Vidra\Acesso\Domain\Papel;

/**
 * Barra a área que o papel do usuário não usa. Roda depois do middleware
 * 'laboratorio', que já conferiu o vínculo. Uso: ->middleware('area:bancada').
 */
final class ExigirArea
{
    public function handle(Request $request, Closure $next, string $area): Response
    {
        $laboratorio = $request->attributes->get('laboratorio');
        $papel = $laboratorio instanceof LaboratorioDoUsuario ? Papel::tryFrom($laboratorio->papel) : null;

        abort_unless($papel?->pode($area) === true, 403, 'Seu papel neste laboratório não dá acesso a esta área.');

        return $next($request);
    }
}
