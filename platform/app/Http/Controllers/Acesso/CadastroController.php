<?php

declare(strict_types=1);

namespace App\Http\Controllers\Acesso;

use App\Http\Controllers\Controller;
use App\Http\Requests\Acesso\RegistrarLaboratorioRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Acesso\Application\RegistrarLaboratorio\RegistrarLaboratorio;
use Vidra\Acesso\Domain\CnpjJaCadastrado;
use Vidra\Acesso\Domain\Cobranca;
use Vidra\Acesso\Domain\EmailJaCadastrado;
use Vidra\Acesso\Domain\Plano;
use Vidra\Shared\Domain\DomainException;
use Vidra\Shared\Domain\ValueObjects\Uf;

final class CadastroController extends Controller
{
    public function create(Request $request): Response
    {
        $plano = $request->query('plano');
        $cobranca = $request->query('cobranca');

        return Inertia::render('acesso/cadastro', [
            'planoInicial' => is_string($plano) ? Plano::tryFrom($plano)?->value : null,
            'cobrancaInicial' => is_string($cobranca) ? Cobranca::tryFrom($cobranca)?->value : null,
            'ufs' => array_map(static fn (Uf $uf): array => ['value' => $uf->value, 'nome' => $uf->nome()], Uf::cases()),
            'planos' => array_map(static fn (Plano $plano): array => [
                'value' => $plano->value, 'nome' => $plano->nome(),
                'preco' => $plano->precoEmCentavos(), 'pedidos' => $plano->pedidos(), 'usuarios' => $plano->usuarios(), 'unidades' => $plano->unidades(),
            ], Plano::cases()),
        ]);
    }

    public function store(RegistrarLaboratorioRequest $request, RegistrarLaboratorio $registrar): RedirectResponse
    {
        $input = $request->toInput();
        try {
            $registrar($input);
        } catch (DomainException $exception) {
            // InvalidValue vem como "campo: motivo"; as exceções de negócio já trazem a mensagem pronta.
            [$campo, $mensagem] = match (true) {
                $exception instanceof CnpjJaCadastrado => ['cnpj', $exception->getMessage()],
                $exception instanceof EmailJaCadastrado => ['email', $exception->getMessage()],
                default => array_map('trim', explode(':', $exception->getMessage(), 2)) + [1 => $exception->getMessage()],
            };
            throw ValidationException::withMessages([$campo => ucfirst($mensagem)]);
        }
        Auth::attempt(['email' => mb_strtolower(trim($input->email)), 'password' => $input->senha]);
        $request->session()->regenerate();
        $fim = now('UTC')->addDays(14)->setTimezone('America/Sao_Paulo')->format('d/m/Y');

        return to_route('dashboard')->with('success', "{$input->laboratorioNome} cadastrado. Seu teste vai até {$fim}.");
    }
}
