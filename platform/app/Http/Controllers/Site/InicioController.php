<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Acesso\Domain\Cobranca;
use Vidra\Acesso\Domain\Plano;

final class InicioController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('site/inicio', [
            'planos' => array_map(static fn (Plano $plano): array => [
                'value' => $plano->value,
                'nome' => $plano->nome(),
                'mensal' => $plano->precoEmCentavos(Cobranca::Mensal),
                'anual' => $plano->precoEmCentavos(Cobranca::Anual),
                'pedidos' => $plano->pedidos(),
                'usuarios' => $plano->usuarios(),
                'unidades' => $plano->unidades(),
            ], Plano::cases()),
        ]);
    }
}
