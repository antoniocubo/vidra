<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Laudos\Application\ListarLaudos\ListarLaudos;
use Vidra\Laudos\Application\ListarLaudos\ListarLaudosInput;

final class LaudoController
{
    public function index(Request $request, ListarLaudos $listar): Response
    {
        $request->validate([
            'busca' => ['nullable', 'string', 'max:255'],
            'de' => ['nullable', 'date_format:Y-m-d'],
            'ate' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:de'],
            'entrega' => ['nullable', Rule::in(['todas', 'entregue', 'falhou', 'pendente', 'nao_aberto'])],
            'pagina' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ]);
        $input = new ListarLaudosInput($request->string('busca')->value(), $request->filled('de') ? $request->string('de')->value() : null,
            $request->filled('ate') ? $request->string('ate')->value() : null, $request->filled('entrega') ? $request->string('entrega')->value() : 'todas', $request->integer('pagina', 1));
        $lista = $listar($input);

        return Inertia::render('lab/laudos/index', ['laudos' => $lista->laudos, 'pagina' => $lista->pagina, 'temProxima' => $lista->temProxima,
            'filtros' => ['busca' => $input->busca, 'de' => $input->de ?? '', 'ate' => $input->ate ?? '', 'entrega' => $input->entrega]]);
    }
}
