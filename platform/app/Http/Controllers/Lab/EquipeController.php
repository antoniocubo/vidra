<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\ConvidarPessoaRequest;
use App\Http\Requests\Lab\TrocarPapelRequest;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Acesso\Application\CancelarConvite;
use Vidra\Acesso\Application\ConvidarPessoa;
use Vidra\Acesso\Application\ListarEquipe;
use Vidra\Acesso\Application\ReenviarConvite;
use Vidra\Acesso\Application\RemoverDaEquipe;
use Vidra\Acesso\Application\TrocarPapel;
use Vidra\Acesso\Domain\Papel;
use Vidra\Shared\Domain\DomainException;

final class EquipeController extends Controller
{
    public function index(ListarEquipe $listar): Response
    {
        return Inertia::render('lab/equipe/index', $listar() + ['papeis' => array_map(static fn (Papel $p): array => ['value' => $p->value, 'label' => $p->rotulo()], Papel::cases())]);
    }

    public function store(ConvidarPessoaRequest $request, ConvidarPessoa $convidar): RedirectResponse
    {
        return $this->executar(fn () => $convidar($request->toInput()), 'Convite enviado.');
    }

    public function reenviar(string $convite, ReenviarConvite $reenviar): RedirectResponse
    {
        return $this->executar(fn () => $reenviar($convite), 'Convite reenviado com nova validade.');
    }

    public function cancelar(string $convite, CancelarConvite $cancelar): RedirectResponse
    {
        return $this->executar(fn () => $cancelar($convite), 'Convite cancelado.');
    }

    public function trocar(TrocarPapelRequest $request, int $usuario, TrocarPapel $trocar): RedirectResponse
    {
        return $this->executar(fn () => $trocar((int) $request->user()?->getAuthIdentifier(), $usuario, $request->string('papel')->value()), 'Papel atualizado.');
    }

    public function remover(Request $request, int $usuario, RemoverDaEquipe $remover): RedirectResponse
    {
        return $this->executar(fn () => $remover((int) $request->user()?->getAuthIdentifier(), $usuario), 'Pessoa removida do laboratório.');
    }

    /** @param Closure(): mixed $acao */
    private function executar(Closure $acao, string $mensagem): RedirectResponse
    {
        try {
            $acao();
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['form' => $e->getMessage()]);
        }

        return to_route('lab.equipe.index')->with('success', $mensagem);
    }
}
