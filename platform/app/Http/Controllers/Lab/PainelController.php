<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Painel\Application\ConsultarPainel;

final class PainelController
{
    public function __invoke(Request $request, ConsultarPainel $consultar): Response
    {
        $nome = explode(' ', trim((string) $request->user('web')?->name))[0];

        return Inertia::render('dashboard', ['painel' => $consultar(), 'primeiroNome' => $nome]);
    }
}
