<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Vidra\Acesso\Application\Assinatura\EventoDoAsaas;
use Vidra\Acesso\Application\Assinatura\ProcessarEventoDoAsaas;

final class AsaasController extends Controller
{
    public function __invoke(Request $request, ProcessarEventoDoAsaas $processar): JsonResponse
    {
        $esperado = config()->string('services.asaas.webhook_token', '');
        // Sem token configurado ninguém é autêntico: fecha em vez de aceitar tudo.
        abort_if($esperado === '' || ! hash_equals($esperado, (string) $request->header('asaas-access-token')), 401);

        $pagamento = (array) $request->input('payment', []);
        $processar(new EventoDoAsaas(
            $request->string('event')->value(), (string) ($pagamento['id'] ?? ''), (string) ($pagamento['customer'] ?? ''),
            (int) round((float) ($pagamento['value'] ?? 0) * 100), (string) ($pagamento['dueDate'] ?? ''),
            is_string($pagamento['invoiceUrl'] ?? null) ? $pagamento['invoiceUrl'] : null,
        ));

        return response()->json(['ok' => true]);
    }
}
