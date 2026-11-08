<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\AceitarAmostraComRessalvaRequest;
use App\Http\Requests\Lab\ReceberAmostraRequest;
use App\Http\Requests\Lab\RejeitarAmostraRequest;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Pedidos\Application\AceitarAmostraComRessalva\AceitarAmostraComRessalva;
use Vidra\Pedidos\Application\PainelDeAmostras\PainelDeAmostras;
use Vidra\Pedidos\Application\ReceberAmostra\ReceberAmostra;
use Vidra\Pedidos\Application\RejeitarAmostra\RejeitarEPedirRecoleta;
use Vidra\Pedidos\Domain\Amostra\Exceptions\AmostraNaoEncontrada;
use Vidra\Shared\Domain\DomainException;

/** Adaptador HTTP do recebimento e da triagem de amostras. */
final class AmostraController extends Controller
{
    public function index(Request $request, PainelDeAmostras $painel): Response
    {
        $request->validate(['data' => ['nullable', 'date_format:Y-m-d'], 'amostra' => ['nullable', 'uuid']]);
        $dados = $this->executar(fn () => $painel(
            $request->filled('data') ? $request->string('data')->value() : null,
            $request->filled('amostra') ? $request->string('amostra')->value() : null,
        ));

        return Inertia::render('lab/amostras/index', [
            'data' => $dados->data,
            'recebidas' => $dados->recebidas,
            'aguardando' => $dados->aguardando,
            'selecionada' => $dados->selecionada,
        ]);
    }

    public function receber(ReceberAmostraRequest $request, ReceberAmostra $receber): RedirectResponse
    {
        try {
            $recebida = $receber($request->string('codigo')->value(), (int) $request->user()?->getAuthIdentifier());
        } catch (AmostraNaoEncontrada $e) {
            throw ValidationException::withMessages(['codigo' => $e->getMessage()]);
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['codigo' => $e->getMessage()]);
        }

        return to_route('lab.amostras.index')->with('success', $recebida->mensagem());
    }

    public function rejeitar(string $amostra, RejeitarAmostraRequest $request, RejeitarEPedirRecoleta $rejeitar): RedirectResponse
    {
        $novoCodigo = $this->executar(fn () => $rejeitar($request->toInput($amostra)));

        return to_route('lab.amostras.index', ['amostra' => $amostra])->with('success', "Amostra rejeitada. A recoleta ficou com o código {$novoCodigo}.");
    }

    public function aceitarComRessalva(string $amostra, AceitarAmostraComRessalvaRequest $request, AceitarAmostraComRessalva $aceitar): RedirectResponse
    {
        $this->executar(fn () => $aceitar($amostra, $request->string('ressalva')->value()));

        return to_route('lab.amostras.index', ['amostra' => $amostra])->with('success', 'Amostra aceita com ressalva. O texto vai para o laudo.');
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $acao
     * @return T
     */
    private function executar(Closure $acao): mixed
    {
        try {
            return $acao();
        } catch (AmostraNaoEncontrada) {
            abort(404);
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['form' => $e->getMessage()]);
        }
    }
}
