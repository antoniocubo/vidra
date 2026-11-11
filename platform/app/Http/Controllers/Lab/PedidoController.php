<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\AbrirPedidoRequest;
use App\Http\Requests\Lab\CancelarRequest;
use App\Http\Requests\Lab\IncluirExameRequest;
use Closure;
use DateTimeZone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Vidra\Pedidos\Application\AbrirPedido\AbrirPedido;
use Vidra\Pedidos\Application\BuscarExames\BuscarExames;
use Vidra\Pedidos\Application\BuscarPacientes\BuscarPacientes;
use Vidra\Pedidos\Application\CancelarExame\CancelarExame;
use Vidra\Pedidos\Application\CancelarPedido\CancelarPedido;
use Vidra\Pedidos\Application\DetalharPedido\DetalharPedido;
use Vidra\Pedidos\Application\GerarEtiquetas\GerarEtiquetas;
use Vidra\Pedidos\Application\IncluirExame\IncluirExame;
use Vidra\Pedidos\Application\ListarPedidos\ListarPedidos;
use Vidra\Pedidos\Application\ListarPedidos\ListarPedidosInput;
use Vidra\Pedidos\Application\PrepararNovoPedido\PrepararNovoPedido;
use Vidra\Pedidos\Domain\Pedido\Exceptions\PedidoNaoEncontrado;
use Vidra\Pedidos\Domain\Pedido\Exceptions\ReferenciaInvalida;
use Vidra\Pedidos\Domain\Pedido\SituacaoDoPedido;
use Vidra\Shared\Domain\DomainException;

/** Adaptador HTTP dos pedidos do laboratório. */
final class PedidoController extends Controller
{
    public function index(Request $request, ListarPedidos $listar): Response
    {
        $request->validate([
            'data' => ['nullable', 'date_format:Y-m-d'],
            'situacao' => ['nullable', Rule::enum(SituacaoDoPedido::class)],
            'busca' => ['nullable', 'string', 'max:255'],
        ]);
        $lista = $listar(new ListarPedidosInput(
            $request->filled('data') ? $request->string('data')->value() : null,
            $request->filled('situacao') ? $request->string('situacao')->value() : null,
            $request->string('busca')->value(),
        ));

        return Inertia::render('lab/pedidos/index', [
            'pedidos' => $lista->pedidos,
            'filtros' => ['data' => $lista->data, 'situacao' => $request->string('situacao')->value(), 'busca' => $request->string('busca')->value()],
            'situacoes' => array_map(fn (SituacaoDoPedido $s): array => ['value' => $s->value, 'label' => $s->rotulo()], SituacaoDoPedido::cases()),
        ]);
    }

    public function create(Request $request, PrepararNovoPedido $preparar, BuscarPacientes $pacientes, BuscarExames $exames): Response
    {
        $request->validate(['animal' => ['nullable', 'uuid'], 'busca_paciente' => ['nullable', 'string', 'max:100'], 'busca_exame' => ['nullable', 'string', 'max:100']]);
        $novo = $preparar($request->filled('animal') ? $request->string('animal')->value() : null);

        return Inertia::render('lab/pedidos/create', [
            'solicitantes' => $novo->solicitantes,
            'clinicasAtivas' => $novo->clinicasAtivas,
            'pacienteInicial' => $novo->paciente,
            'textos' => $novo->textos,
            'pacientes' => fn (): array => $pacientes($request->string('busca_paciente')->value()),
            'exames' => fn (): array => $exames($request->string('busca_exame')->value()),
            'links' => [
                'novoSolicitante' => route('lab.veterinarios.create'),
                'novoPaciente' => route('lab.tutores.create', ['voltar' => 'pedido']),
            ],
        ]);
    }

    public function store(AbrirPedidoRequest $request, AbrirPedido $abrir): RedirectResponse
    {
        $aberto = $this->executar(fn () => $abrir($request->toInput()));

        return to_route('lab.pedidos.show', ['pedido' => $aberto->pedidoId, 'imprimir' => 1])
            ->with('success', "Pedido {$aberto->protocolo} salvo.");
    }

    public function show(Request $request, string $pedido, DetalharPedido $detalhar, BuscarExames $exames, \Vidra\Laudos\Application\LaudoDoPedido $laudo, \Vidra\Laudos\Application\ConsultarEntrega $entrega, \Vidra\Auditoria\Application\HistoricoDoPedido $historico): Response
    {
        $request->validate(['busca_exame' => ['nullable', 'string', 'max:100']]);
        $liberado = $laudo($pedido);

        return Inertia::render('lab/pedidos/show', [
            'pedido' => $this->executar(fn () => $detalhar($pedido)),
            'imprimir' => $request->boolean('imprimir'),
            'exames' => fn (): array => $exames($request->string('busca_exame')->value()),
            'novaAmostra' => $request->boolean('nova_amostra'),
            'entrega' => $entrega($pedido),
            'historico' => $historico($pedido),
            'laudo' => $liberado === null ? null : [
                'numero' => $liberado->numero, 'rt' => $liberado->rtNome,
                'registro' => $liberado->rtRegistro, 'usuario' => $liberado->usuarioNome,
                'quando' => $liberado->liberadoEm->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i'),
                'codigo' => $liberado->codigoAcesso,
                'reabertoMotivo' => $liberado->reabertoMotivo,
                'versoes' => array_map(fn ($v): array => ['versao' => $v->versao, 'em' => $v->liberadoEm->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i'), 'por' => $v->usuarioNome, 'motivo' => $v->motivo, 'pdf' => $v->pdfCaminho === null ? null : route('lab.pedidos.laudo.versao.pdf', ['pedido' => $pedido, 'versao' => $v->versao])], $liberado->versoes),
            ],
        ]);
    }

    public function cancelar(CancelarRequest $request, string $pedido, CancelarPedido $cancelar): RedirectResponse
    {
        $this->executar(fn () => $cancelar($request->paraPedido($pedido)));

        return to_route('lab.pedidos.show', ['pedido' => $pedido])->with('success', 'Pedido cancelado.');
    }

    public function cancelarExame(CancelarRequest $request, string $pedido, string $item, CancelarExame $cancelar): RedirectResponse
    {
        $this->executar(fn () => $cancelar($request->paraExame($pedido, $item)));

        return to_route('lab.pedidos.show', ['pedido' => $pedido])->with('success', 'Exame cancelado.');
    }

    public function incluirExame(IncluirExameRequest $request, string $pedido, IncluirExame $incluir): RedirectResponse
    {
        $nova = $this->executar(fn () => $incluir($request->toInput($pedido)));

        return to_route('lab.pedidos.show', ['pedido' => $pedido, ...($nova ? ['nova_amostra' => 1] : [])])
            ->with('success', $nova ? 'Exame incluído. Imprima a etiqueta da nova amostra.' : 'Exame incluído na amostra pendente.');
    }

    public function etiquetas(string $pedido, GerarEtiquetas $gerar): View
    {
        return view('etiquetas.pedido', ['etiquetas' => $this->executar(fn () => $gerar($pedido))]);
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
        } catch (PedidoNaoEncontrado) {
            abort(404);
        } catch (ReferenciaInvalida $e) {
            throw ValidationException::withMessages([$e->campo => $e->getMessage()]);
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['form' => $e->getMessage()]);
        }
    }
}
