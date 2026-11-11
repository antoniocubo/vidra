<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\AssinarPlanoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Acesso\Application\Assinatura\AssinarPlano;
use Vidra\Acesso\Application\Assinatura\ResumoDaAssinatura;
use Vidra\Acesso\Domain\Cobranca;
use Vidra\Acesso\Domain\Plano;
use Vidra\Shared\Domain\DomainException;

final class AssinaturaController extends Controller
{
    public function index(ResumoDaAssinatura $resumo): Response
    {
        return Inertia::render('lab/assinatura/index', $resumo() + [
            'planos' => array_map(static fn (Plano $plano): array => [
                'value' => $plano->value, 'nome' => $plano->nome(), 'mensal' => $plano->precoEmCentavos(Cobranca::Mensal),
                'anual' => $plano->precoEmCentavos(Cobranca::Anual), 'pedidos' => $plano->pedidos(),
                'usuarios' => $plano->usuarios(), 'unidades' => $plano->unidades(),
            ], Plano::cases()),
        ]);
    }

    public function store(AssinarPlanoRequest $request, AssinarPlano $assinar): RedirectResponse
    {
        try {
            $assinar($request->toInput());
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['form' => $e->getMessage()]);
        }

        return to_route('lab.assinatura.index')->with('success', 'Plano escolhido. A cobrança está na lista abaixo.');
    }
}
