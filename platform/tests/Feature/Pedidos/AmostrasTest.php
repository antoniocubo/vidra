<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\CenarioDePedidos;
use Tests\Support\Laboratorios;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    $this->lab = Laboratorios::criar();
    $this->usuario = Laboratorios::usuarioEm($this->lab);
    $this->c = CenarioDePedidos::criar($this->lab);
    actingAs($this->usuario)->post(route('lab.pedidos.store'), [
        'paciente_id' => $this->c['animal'], 'solicitante_id' => $this->c['vinculo'], 'pagador' => 'clinica', 'clinica_id' => $this->c['clinica'],
        'prioridade' => 'rotina', 'exame_ids' => [$this->c['bioquimico'], $this->c['hemograma']],
    ])->assertSessionHasNoErrors();
    $this->amostra = fn (string $codigo): string => (string) DB::table('amostras')->where('codigo', $codigo)->value('id');
});

it('recebe a amostra pelo código bipado e mostra o aviso', function (): void {
    actingAs($this->usuario)->post(route('lab.amostras.receber'), ['codigo' => ' a-0001-1 '])
        ->assertRedirect(route('lab.amostras.index'))->assertSessionHas('success', 'A-0001-1 recebida · Thor · Soro');

    assertDatabaseHas('amostras', ['codigo' => 'A-0001-1', 'situacao' => 'recebida', 'recebida_por' => $this->usuario->id]);
    assertDatabaseHas('pedido_itens', ['codigo' => 'BIOQ', 'situacao' => 'em_analise']);
    assertDatabaseHas('pedido_itens', ['codigo' => 'HEMO', 'situacao' => 'aguardando_amostra']);
});

it('avisa a hora em que a amostra já foi recebida', function (): void {
    $this->travelTo(now()->setTime(13, 35));
    actingAs($this->usuario)->post(route('lab.amostras.receber'), ['codigo' => 'A-0001-1']);

    actingAs($this->usuario)->post(route('lab.amostras.receber'), ['codigo' => 'A-0001-1'])
        ->assertSessionHasErrors(['codigo' => 'A amostra A-0001-1 já foi recebida às 10:35.']);
});

it('diz que não há amostra quando o código não existe ou é de outro laboratório', function (): void {
    $outro = Laboratorios::criar('Laboratório Beta');
    $cb = CenarioDePedidos::criar($outro, 'Mel', '19991112222');
    $beta = Laboratorios::usuarioEm($outro);
    actingAs($beta)->post(route('lab.pedidos.store'), [
        'paciente_id' => $cb['animal'], 'solicitante_id' => $cb['vinculo'], 'pagador' => 'responsavel', 'prioridade' => 'rotina', 'exame_ids' => [$cb['bioquimico']],
    ])->assertSessionHasNoErrors();

    // Os dois laboratórios têm A-0001-1: cada um enxerga o seu.
    actingAs($beta)->post(route('lab.amostras.receber'), ['codigo' => 'A-0001-1'])->assertSessionHas('success', 'A-0001-1 recebida · Mel · Soro');
    expect(DB::table('amostras')->where('laboratorio_id', $this->lab)->where('codigo', 'A-0001-1')->value('situacao'))->toBe('aguardando');

    actingAs($this->usuario)->post(route('lab.amostras.receber'), ['codigo' => 'A-9999-1'])->assertSessionHasErrors(['codigo' => 'Nenhuma amostra com o código A-9999-1 neste laboratório.']);
    actingAs($this->usuario)->post(route('lab.amostras.receber'), ['codigo' => ''])->assertSessionHasErrors('codigo');
});

it('rejeita e pede a recoleta com o próximo código', function (): void {
    actingAs($this->usuario)->post(route('lab.amostras.receber'), ['codigo' => 'A-0001-1']);
    $id = ($this->amostra)('A-0001-1');

    actingAs($this->usuario)->post(route('lab.amostras.rejeitar', ['amostra' => $id]), ['motivo' => 'hemolise'])
        ->assertRedirect(route('lab.amostras.index', ['amostra' => $id]))->assertSessionHas('success', 'Amostra rejeitada. A recoleta ficou com o código A-0001-3.');

    assertDatabaseHas('amostras', ['codigo' => 'A-0001-1', 'situacao' => 'rejeitada', 'motivo_rejeicao' => 'hemolise', 'recoleta_codigo' => 'A-0001-3']);
    assertDatabaseHas('amostras', ['codigo' => 'A-0001-3', 'situacao' => 'aguardando', 'material' => 'Soro']);
    assertDatabaseHas('pedido_itens', ['codigo' => 'BIOQ', 'situacao' => 'aguardando_amostra']);
    expect(DB::table('amostra_itens')->where('amostra_id', ($this->amostra)('A-0001-3'))->count())->toBe(1);

    actingAs($this->usuario)->post(route('lab.amostras.rejeitar', ['amostra' => $id]), ['motivo' => 'hemolise'])->assertSessionHasErrors('form');
});

it('exige o texto quando o motivo é outro e não rejeita sem motivo', function (): void {
    $id = ($this->amostra)('A-0001-1');

    actingAs($this->usuario)->post(route('lab.amostras.rejeitar', ['amostra' => $id]), ['motivo' => 'outro'])->assertSessionHasErrors('texto');
    actingAs($this->usuario)->post(route('lab.amostras.rejeitar', ['amostra' => $id]), [])->assertSessionHasErrors('motivo');
    assertDatabaseHas('amostras', ['codigo' => 'A-0001-1', 'situacao' => 'aguardando']);
});

it('aceita com ressalva depois de receber', function (): void {
    $id = ($this->amostra)('A-0001-2');
    actingAs($this->usuario)->post(route('lab.amostras.ressalva', ['amostra' => $id]), ['ressalva' => 'Amostra levemente lipêmica'])->assertSessionHasErrors('form');

    actingAs($this->usuario)->post(route('lab.amostras.receber'), ['codigo' => 'A-0001-2']);
    actingAs($this->usuario)->post(route('lab.amostras.ressalva', ['amostra' => $id]), ['ressalva' => ''])->assertSessionHasErrors('ressalva');
    actingAs($this->usuario)->post(route('lab.amostras.ressalva', ['amostra' => $id]), ['ressalva' => 'Amostra levemente lipêmica'])
        ->assertRedirect(route('lab.amostras.index', ['amostra' => $id]));

    assertDatabaseHas('amostras', ['codigo' => 'A-0001-2', 'situacao' => 'aceita_com_ressalva', 'ressalva' => 'Amostra levemente lipêmica']);
});

it('mostra as recebidas de hoje, as que aguardam e o painel da amostra selecionada', function (): void {
    actingAs($this->usuario)->post(route('lab.amostras.receber'), ['codigo' => 'A-0001-1']);
    $id = ($this->amostra)('A-0001-1');

    actingAs($this->usuario)->get(route('lab.amostras.index', ['amostra' => $id]))->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('lab/amostras/index')
            ->has('recebidas', 1)->where('recebidas.0.codigo', 'A-0001-1')->where('recebidas.0.pacienteNome', 'Thor')->where('recebidas.0.pagador', 'Clínica Patas')->where('recebidas.0.situacao', 'recebida')
            ->has('aguardando', 1)->where('aguardando.0.codigo', 'A-0001-2')
            ->where('selecionada.pedidoId', DB::table('pedidos')->value('id'))
            ->where('selecionada.exames', ['Bioquímico renal e hepático'])
            ->where('selecionada.mensagens.hemolise', 'Amostra A-0001-1 do Thor chegou hemolisada. Precisamos de nova coleta de soro para Bioquímico renal e hepático.'),
    );
});

it('devolve 404 para amostra de outro laboratório', function (): void {
    $id = ($this->amostra)('A-0001-1');
    $outro = Laboratorios::usuario();

    actingAs($outro)->get(route('lab.amostras.index', ['amostra' => $id]))->assertNotFound();
    actingAs($outro)->post(route('lab.amostras.rejeitar', ['amostra' => $id]), ['motivo' => 'hemolise'])->assertNotFound();
    actingAs($outro)->post(route('lab.amostras.ressalva', ['amostra' => $id]), ['ressalva' => 'x'])->assertNotFound();
});
