<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\ConcederCreditoRequest;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Vidra\Financeiro\Application\ConcederCredito;
use Vidra\Financeiro\Application\EnviarFatura\BaixarFatura;
use Vidra\Financeiro\Application\EnviarFatura\ReenviarFatura;
use Vidra\Financeiro\Application\FecharFaturas\FecharFaturasAgora;
use Vidra\Financeiro\Application\PainelFinanceiro\PainelFinanceiro;
use Vidra\Financeiro\Application\RegistrarPagamento;
use Vidra\Shared\Domain\DomainException;

final class FinanceiroController extends Controller
{
    public function index(Request $request, PainelFinanceiro $painel): InertiaResponse
    {
        $competencia = $request->query('competencia');
        $fatura = $request->query('fatura');

        return Inertia::render('lab/financeiro/index', $this->tentar(fn (): array => $painel(is_string($competencia) ? $competencia : null, is_string($fatura) ? $fatura : null)));
    }

    public function fechar(FecharFaturasAgora $fechar): RedirectResponse
    {
        $fechadas = $this->tentar($fechar(...));

        return back()->with('success', match ($fechadas) {
            0 => 'Não há pedidos novos para faturar no mês anterior.',
            1 => '1 fatura fechada e colocada na fila de envio.',
            default => "{$fechadas} faturas fechadas e colocadas na fila de envio.",
        });
    }

    public function pdf(string $fatura, BaixarFatura $baixar): Response
    {
        $arquivo = $this->tentar(fn (): array => $baixar($fatura));

        return response($arquivo['bytes'])->withHeaders(['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$arquivo['nome'].'"', 'Cache-Control' => 'private, no-store']);
    }

    public function reenviar(string $fatura, ReenviarFatura $reenviar): RedirectResponse
    {
        $this->tentar(fn () => $reenviar($fatura));

        return back()->with('success', 'Envio da fatura por e-mail colocado na fila.');
    }

    public function pagamento(Request $request, string $fatura, RegistrarPagamento $registrar): RedirectResponse
    {
        $this->tentar(fn () => $registrar($fatura, (int) $request->user()?->getAuthIdentifier()));

        return back()->with('success', 'Pagamento registrado.');
    }

    public function credito(ConcederCreditoRequest $request, string $fatura, ConcederCredito $conceder): RedirectResponse
    {
        $this->tentar(fn () => $conceder($request->toInput($fatura)));

        return back()->with('success', 'Crédito concedido.');
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $acao
     * @return T
     */
    private function tentar(Closure $acao): mixed
    {
        try {
            return $acao();
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['form' => $e->getMessage()]);
        }
    }
}
