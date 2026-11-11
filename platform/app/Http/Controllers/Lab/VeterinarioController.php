<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\AlterarVinculoRequest;
use App\Http\Requests\Lab\RegistrarVeterinarioRequest;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Shared\Domain\DomainException;
use Vidra\Shared\Domain\ValueObjects\Uf;
use Vidra\Veterinaria\Application\AlterarVinculo\AlterarVinculo;
use Vidra\Veterinaria\Application\Clinica\ClinicaDto;
use Vidra\Veterinaria\Application\DetalharVinculo\DetalharVinculo;
use Vidra\Veterinaria\Application\ListarClinicas\ListarClinicas;
use Vidra\Veterinaria\Application\ListarVeterinarios\ClinicaDoVeterinarioDto;
use Vidra\Veterinaria\Application\ListarVeterinarios\ListarVeterinarios;
use Vidra\Veterinaria\Application\RegistrarVeterinario\RegistrarVeterinario;
use Vidra\Veterinaria\Domain\Clinica\Exceptions\ClinicaNaoEncontrada;
use Vidra\Veterinaria\Domain\Veterinario\Tratamento;
use Vidra\Veterinaria\Domain\Vinculo\Exceptions\VeterinarioJaVinculado;
use Vidra\Veterinaria\Domain\Vinculo\Exceptions\VinculoNaoEncontrado;

/**
 * Adaptador HTTP: traduz a requisição para o caso de uso e o resultado para Inertia.
 */
final class VeterinarioController extends Controller
{
    public function index(Request $request, ListarVeterinarios $listar, DetalharVinculo $detalhar): Response
    {
        $request->validate(['busca' => ['nullable', 'string', 'max:255'], 'veterinario' => ['nullable', 'uuid']]);
        $busca = $request->string('busca')->value();
        $veterinarios = $listar($busca);
        $id = $request->filled('veterinario') ? $request->string('veterinario')->value() : ($veterinarios[0]->vinculoId ?? null);

        return Inertia::render('lab/veterinarios/index', [
            'veterinarios' => $veterinarios,
            'selecionado' => $id === null ? null : $this->executar(fn () => $detalhar($id)),
            'busca' => $busca,
        ]);
    }

    public function create(ListarClinicas $clinicas): Response
    {
        return Inertia::render('lab/veterinarios/create', [
            'tratamentos' => array_column(Tratamento::cases(), 'value'),
            'ufs' => array_column(Uf::cases(), 'value'),
            'clinicas' => $this->opcoesDeClinica($clinicas(), []),
        ]);
    }

    public function store(RegistrarVeterinarioRequest $request, RegistrarVeterinario $registrar): RedirectResponse
    {
        try {
            $resultado = $registrar($request->toInput());
        } catch (VeterinarioJaVinculado $e) {
            throw ValidationException::withMessages(['crmv_numero' => $e->getMessage()]);
        } catch (DomainException $e) {
            throw ValidationException::withMessages([$e instanceof ClinicaNaoEncontrada ? 'clinica_ids' : 'form' => $e->getMessage()]);
        }

        return to_route('lab.veterinarios.index', ['veterinario' => $resultado->vinculoId->value])->with('success', $resultado->reaproveitado
            ? $resultado->nomeDeExibicao.' já estava no Vidra e agora faz parte do seu laboratório.'
            : 'Veterinário cadastrado.');
    }

    public function edit(string $vinculo, DetalharVinculo $detalhar, \Vidra\Veterinaria\Application\AcessoDoVeterinario\ConsultarAcessoDoVeterinario $acesso, ListarClinicas $clinicas): Response
    {
        $veterinario = $this->executar(fn () => $detalhar($vinculo));

        return Inertia::render('lab/veterinarios/edit', [
            'veterinario' => $veterinario,
            'emailDeAcesso' => $acesso($vinculo),
            'clinicas' => $this->opcoesDeClinica($clinicas(), $veterinario->clinicas),
        ]);
    }

    public function update(string $vinculo, AlterarVinculoRequest $request, AlterarVinculo $alterar): RedirectResponse
    {
        $this->executar(fn () => $alterar($request->toInput($vinculo)));

        return to_route('lab.veterinarios.index', ['veterinario' => $vinculo])->with('success', 'Cadastro do veterinário atualizado.');
    }

    /**
     * Clínicas ativas para marcar, mais as inativas onde ele já atende (para não sumirem sem aviso).
     *
     * @param  list<ClinicaDto>  $ativas
     * @param  list<ClinicaDoVeterinarioDto>  $doVeterinario
     * @return list<array{id: string, nome: string, ativa: bool}>
     */
    private function opcoesDeClinica(array $ativas, array $doVeterinario): array
    {
        $opcoes = array_map(fn (ClinicaDto $c): array => ['id' => $c->id, 'nome' => $c->nomeFantasia, 'ativa' => true], $ativas);
        $ids = array_column($opcoes, 'id');
        foreach ($doVeterinario as $clinica) {
            if (! in_array($clinica->id, $ids, true)) {
                $opcoes[] = ['id' => $clinica->id, 'nome' => $clinica->nome, 'ativa' => $clinica->ativa];
            }
        }

        return $opcoes;
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
        } catch (VinculoNaoEncontrado) {
            abort(404);
        } catch (ClinicaNaoEncontrada $e) {
            throw ValidationException::withMessages(['clinica_ids' => $e->getMessage()]);
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['form' => $e->getMessage()]);
        }
    }
}
