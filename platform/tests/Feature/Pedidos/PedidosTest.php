<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\CenarioDePedidos;
use Tests\Support\Laboratorios;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

/** @return array<string, mixed> */
function payloadPedido(array $c, array $overrides = []): array
{
    return array_merge([
        'paciente_id' => $c['animal'], 'solicitante_id' => $c['vinculo'], 'pagador' => 'clinica', 'clinica_id' => $c['clinica'],
        'prioridade' => 'rotina', 'exame_ids' => [$c['bioquimico'], $c['hemograma']], 'observacao' => null,
    ], $overrides);
}

beforeEach(function (): void {
    $this->lab = Laboratorios::criar();
    $this->usuario = Laboratorios::usuarioEm($this->lab);
    $this->c = CenarioDePedidos::criar($this->lab);
});

it('exige login', function (): void {
    auth()->logout();
    $this->get(route('lab.pedidos.index'))->assertRedirect(route('login'));
    $this->post(route('lab.pedidos.store'), [])->assertRedirect(route('login'));
});

it('abre o pedido completo com os adaptadores da linha Pet e redireciona para o detalhe', function (): void {
    $resposta = actingAs($this->usuario)->post(route('lab.pedidos.store'), payloadPedido($this->c, ['prioridade' => 'urgente', 'observacao' => 'Jejum de 8h']));

    $id = DB::table('pedidos')->value('id');
    $resposta->assertRedirect(route('lab.pedidos.show', ['pedido' => $id, 'imprimir' => 1]))->assertSessionHas('success', 'Pedido P-00001 salvo.');
    assertDatabaseHas('pedidos', ['laboratorio_id' => $this->lab, 'protocolo' => 1, 'paciente_id' => $this->c['animal'], 'solicitante_id' => $this->c['vinculo'], 'pagador_tipo' => 'clinica', 'clinica_id' => $this->c['clinica'], 'prioridade' => 'urgente', 'observacao' => 'Jejum de 8h', 'criado_por' => $this->usuario->id]);
    assertDatabaseHas('pedido_itens', ['pedido_id' => $id, 'codigo' => 'BIOQ', 'preco_tabela_centavos' => 10667, 'desconto_percentual' => 10, 'preco_centavos' => 9600, 'situacao' => 'aguardando_amostra']);
    assertDatabaseHas('pedido_itens', ['pedido_id' => $id, 'codigo' => 'HEMO', 'preco_centavos' => 4200]);
    expect(DB::table('amostras')->orderBy('codigo')->pluck('codigo')->all())->toBe(['A-0001-1', 'A-0001-2'])
        ->and(DB::table('amostra_itens')->count())->toBe(2);

    actingAs($this->usuario)->get(route('lab.pedidos.show', ['pedido' => $id]))->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('lab/pedidos/show')
            ->where('pedido.protocolo', 'P-00001')
            ->where('pedido.paciente.nome', 'Thor')
            ->where('pedido.solicitante.nome', 'Dr. Rafael Lima')
            ->where('pedido.pagador.rotulo', 'Clínica Patas · fatura mensal')
            ->where('pedido.totalCentavos', 13800)
            ->where('pedido.situacaoRotulo', 'Aguardando amostra')
            ->has('pedido.itens', 2)->has('pedido.amostras', 2)->where('pedido.etiquetas', 2),
    );
});

it('numera os protocolos em sequência por laboratório', function (): void {
    actingAs($this->usuario)->post(route('lab.pedidos.store'), payloadPedido($this->c));
    actingAs($this->usuario)->post(route('lab.pedidos.store'), payloadPedido($this->c, ['exame_ids' => [$this->c['hemograma']]]));

    $outro = Laboratorios::criar('Laboratório Beta');
    $cb = CenarioDePedidos::criar($outro, 'Mel', '19991112222');
    actingAs(Laboratorios::usuarioEm($outro))->post(route('lab.pedidos.store'), payloadPedido($cb));

    expect(DB::table('pedidos')->where('laboratorio_id', $this->lab)->orderBy('protocolo')->pluck('protocolo')->all())->toBe([1, 2])
        ->and(DB::table('pedidos')->where('laboratorio_id', $outro)->pluck('protocolo')->all())->toBe([1]);
});

it('valida o pedido', function (array $patch, string $campo): void {
    actingAs($this->usuario)->post(route('lab.pedidos.store'), payloadPedido($this->c, $patch))->assertSessionHasErrors($campo);
    $this->assertDatabaseCount('pedidos', 0);
})->with([
    'sem exame' => [['exame_ids' => []], 'exame_ids'],
    'exame repetido' => [['exame_ids' => ['a', 'a']], 'exame_ids.0'],
    'prioridade inválida' => [['prioridade' => 'agora'], 'prioridade'],
    'clínica faltando' => [['clinica_id' => null], 'clinica_id'],
    'sem paciente' => [['paciente_id' => ''], 'paciente_id'],
]);

it('recusa paciente de outro laboratório e exame inativo', function (): void {
    $outro = Laboratorios::criar('Laboratório Beta');
    $cb = CenarioDePedidos::criar($outro, 'Mel', '19991112222');

    actingAs($this->usuario)->post(route('lab.pedidos.store'), payloadPedido($this->c, ['paciente_id' => $cb['animal']]))->assertSessionHasErrors(['paciente_id' => 'Escolha o paciente do pedido.']);

    DB::table('exames')->where('id', $this->c['hemograma'])->update(['ativo' => false]);
    actingAs($this->usuario)->post(route('lab.pedidos.store'), payloadPedido($this->c))->assertSessionHasErrors('exame_ids');
    $this->assertDatabaseCount('pedidos', 0);
});

it('mostra o formulário com solicitantes, clínicas e o animal pré-selecionado', function (): void {
    actingAs($this->usuario)->get(route('lab.pedidos.create', ['animal' => $this->c['animal'], 'busca_exame' => 'hemo']))->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('lab/pedidos/create')
            ->where('pacienteInicial.nome', 'Thor')
            ->where('pacienteInicial.autorizaEmail', false)
            ->where('solicitantes.0.nome', 'Dr. Rafael Lima')
            ->where('solicitantes.0.pagadorPadrao', 'clinica')
            ->where('solicitantes.0.clinicas.0.descontoPercentual', 10)
            ->where('textos.secaoDoPaciente', 'Tutor e animal')
            ->where('links.novoPaciente', route('lab.tutores.create', ['voltar' => 'pedido']))
            ->has('exames', 1)->where('exames.0.codigo', 'HEMO'),
    );
});

it('busca animais pelo telefone, nome do tutor ou nome do animal', function (string $termo): void {
    actingAs($this->usuario)->get(route('lab.pedidos.create', ['busca_paciente' => $termo]))->assertInertia(
        fn (Assert $page) => $page->has('pacientes', 1)->where('pacientes.0.nome', 'Thor')->where('pacientes.0.especieCurta', 'cão')->where('pacientes.0.responsavelNome', 'Carla Mendes'),
    );
})->with(['99812', 'carla', 'thor']);

it('lista os pedidos do dia com filtros', function (): void {
    actingAs($this->usuario)->post(route('lab.pedidos.store'), payloadPedido($this->c, ['prioridade' => 'urgente']));
    actingAs($this->usuario)->post(route('lab.pedidos.store'), payloadPedido($this->c, ['exame_ids' => [$this->c['hemograma']]]));
    DB::table('amostras')->where('codigo', 'A-0002-1')->update(['situacao' => 'recebida']);
    DB::table('pedido_itens')->where('codigo', 'HEMO')->whereIn('pedido_id', DB::table('pedidos')->where('protocolo', 2)->pluck('id'))->update(['situacao' => 'em_analise']);

    actingAs($this->usuario)->get(route('lab.pedidos.index'))->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('lab/pedidos/index')->has('pedidos', 2)->where('pedidos.1.protocolo', 'P-00001')->where('pedidos.1.urgente', true)->where('pedidos.1.exames', ['BIOQ', 'HEMO']),
    );
    actingAs($this->usuario)->get(route('lab.pedidos.index', ['situacao' => 'em_analise']))->assertInertia(fn (Assert $page) => $page->has('pedidos', 1)->where('pedidos.0.protocolo', 'P-00002'));
    actingAs($this->usuario)->get(route('lab.pedidos.index', ['busca' => 'thor']))->assertInertia(fn (Assert $page) => $page->has('pedidos', 2));
    actingAs($this->usuario)->get(route('lab.pedidos.index', ['busca' => '00001']))->assertInertia(fn (Assert $page) => $page->has('pedidos', 1));
    actingAs($this->usuario)->get(route('lab.pedidos.index', ['busca' => 'ninguem']))->assertInertia(fn (Assert $page) => $page->has('pedidos', 0));
    actingAs($this->usuario)->get(route('lab.pedidos.index', ['data' => '2020-01-01']))->assertInertia(fn (Assert $page) => $page->has('pedidos', 0)->where('filtros.data', '2020-01-01'));
});

it('devolve 404 para pedido de outro laboratório', function (): void {
    actingAs($this->usuario)->post(route('lab.pedidos.store'), payloadPedido($this->c));
    $id = DB::table('pedidos')->value('id');

    $outro = Laboratorios::usuario();
    actingAs($outro)->get(route('lab.pedidos.show', ['pedido' => $id]))->assertNotFound();
    actingAs($outro)->get(route('lab.pedidos.etiquetas', ['pedido' => $id]))->assertNotFound();
});

it('imprime as etiquetas em HTML com o Code 128 de cada amostra', function (): void {
    actingAs($this->usuario)->post(route('lab.pedidos.store'), payloadPedido($this->c));
    $id = DB::table('pedidos')->value('id');

    $resposta = actingAs($this->usuario)->get(route('lab.pedidos.etiquetas', ['pedido' => $id]))->assertOk();

    $resposta->assertSee('<svg', false)->assertSee('size: 50mm 25mm', false)->assertSee('window.print()', false)
        ->assertSeeInOrder(['A-0001-1', 'Soro', 'P-00001'])->assertSee('A-0001-2')->assertSee('Sangue EDTA')->assertSee('Thor');
    expect(substr_count($resposta->getContent(), 'class="etiqueta"'))->toBe(2);
});

it('isola todas as tabelas de pedidos por laboratório', function (): void {
    actingAs($this->usuario)->post(route('lab.pedidos.store'), payloadPedido($this->c));
    $outro = Laboratorios::criar('Laboratório Beta');

    app(ContextoLaboratorio::class)->executarComo($outro, function (): void {
        expect(Vidra\Pedidos\Infrastructure\Persistence\PedidoModel::query()->count())->toBe(0)
            ->and(Vidra\Pedidos\Infrastructure\Persistence\ItemDoPedidoModel::query()->count())->toBe(0)
            ->and(Vidra\Pedidos\Infrastructure\Persistence\AmostraModel::query()->count())->toBe(0)
            ->and(Vidra\Pedidos\Infrastructure\Persistence\AmostraItemModel::query()->count())->toBe(0);
    });
    app(ContextoLaboratorio::class)->executarComo($this->lab, function (): void {
        expect(Vidra\Pedidos\Infrastructure\Persistence\PedidoModel::query()->count())->toBe(1)
            ->and(Vidra\Pedidos\Infrastructure\Persistence\AmostraItemModel::query()->count())->toBe(2);
    });
});
