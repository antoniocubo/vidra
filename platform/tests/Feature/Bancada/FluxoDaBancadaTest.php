<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\CenarioDeBancada;
use Tests\Support\Laboratorios;
use Vidra\Bancada\Infrastructure\Persistence\ResultadoModel;
use Vidra\Laudos\Domain\LaudoLiberado;
use Vidra\Laudos\Infrastructure\Persistence\AvisoModel;
use Vidra\Laudos\Infrastructure\Persistence\LaudoModel;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;

beforeEach(function (): void {
    Illuminate\Support\Facades\Queue::fake();
    $this->lab = Laboratorios::criar();
    $this->usuario = Laboratorios::usuarioEm($this->lab);
    $this->c = CenarioDeBancada::criar($this->lab);
    DB::table('laboratorio_usuario')->where('user_id', $this->usuario->id)->update(['e_rt' => true]);
    $this->actingAs($this->usuario);
});
function abrirBancada($teste): array
{
    $teste->post(route('lab.pedidos.store'), ['paciente_id' => $teste->c['animal'], 'solicitante_id' => $teste->c['vinculo'], 'pagador' => 'responsavel', 'prioridade' => 'urgente', 'exame_ids' => [$teste->c['bioquimico']]])->assertSessionHasNoErrors();
    $pedido = DB::table('pedidos')->where('laboratorio_id', $teste->lab)->orderByDesc('protocolo')->value('id');
    $item = DB::table('pedido_itens')->where('pedido_id', $pedido)->value('id');
    $amostra = DB::table('amostras')->where('pedido_id', $pedido)->value('codigo');
    $teste->post(route('lab.amostras.receber'), ['codigo' => $amostra])->assertSessionHasNoErrors();

    return [$pedido, $item];
}
function digitarBancada($teste, string $item, string $valor): void
{
    $teste->post(route('lab.bancada.salvar', ['item' => $item]), ['valores' => [$teste->c['analito'] => ['valor' => $valor, 'texto' => null]]])->assertSessionHasNoErrors();
}
function enviarBancada($teste, string $item): void
{
    $teste->post(route('lab.bancada.conferir', ['item' => $item]))->assertSessionHasNoErrors();
}
function avisarBancada($teste, string $pedido): void
{
    $resultado = DB::table('resultados')->where('pedido_id', $pedido)->value('id');
    $teste->post(route('lab.liberacao.aviso', ['pedido' => $pedido]), ['resultado_id' => $resultado, 'destinatario' => 'Dr. Rafael Lima'])->assertSessionHasNoErrors();
}
it('executa pedido amostra bancada aviso e liberação com adaptadores reais', function (): void {
    [$pedido,$item] = abrirBancada($this);
    digitarBancada($this, $item, '8,50');
    $this->get(route('lab.bancada.index', ['setor' => 'bioquimica', 'item' => $item]))->assertOk()->assertInertia(fn (Assert $p) => $p->where('selecionado.analitos.0.classificacao', 'critico_alto')->where('laboratorio.eRt', true));
    expect(DB::table('pedido_itens')->where('id', $item)->value('situacao'))->toBe('em_analise');
    enviarBancada($this, $item);
    $this->post(route('lab.liberacao.liberar', ['pedido' => $pedido]))->assertSessionHasErrors(['form' => 'Registre o aviso do valor crítico de Potássio antes de liberar.']);
    expect(DB::table('laudos')->count())->toBe(0);
    avisarBancada($this, $pedido);
    $this->assertDatabaseHas('avisos_valor_critico', ['telefone' => '(19) 3241-0099', 'avisado_por' => $this->usuario->id]);
    Event::fake([LaudoLiberado::class]);
    $this->post(route('lab.liberacao.liberar', ['pedido' => $pedido]), ['observacao' => 'Conferido com a clínica.'])->assertSessionHasNoErrors()->assertSessionHas('success', 'Laudo L-00001 liberado.');
    Event::assertDispatched(LaudoLiberado::class, fn ($e): bool => $e->pedidoId === $pedido && $e->laboratorioId === $this->lab);
    $this->assertDatabaseHas('pedido_itens', ['id' => $item, 'situacao' => 'liberado']);
    $this->assertDatabaseHas('laudos', ['pedido_id' => $pedido, 'rt_nome' => 'Marina Alves', 'rt_registro' => 'CRMV-SP 12345', 'observacao' => 'Conferido com a clínica.']);
    $this->get(route('lab.pedidos.show', ['pedido' => $pedido]))->assertInertia(fn (Assert $p) => $p->where('laudo.numero', 'L-00001')->where('laudo.rt', 'Marina Alves'));
    $this->post(route('lab.bancada.reabrir', ['item' => $item]))->assertSessionHasErrors('form');
    $this->post(route('lab.bancada.salvar', ['item' => $item]), ['valores' => [$this->c['analito'] => ['valor' => '4', 'texto' => null]]])->assertSessionHasErrors('form');
});
it('mostra apenas o anterior liberado e alerta delta inclusivo', function (): void {
    [$anterior,$itemAnt] = abrirBancada($this);
    digitarBancada($this, $itemAnt, '4');
    enviarBancada($this, $itemAnt);
    $this->post(route('lab.liberacao.liberar', ['pedido' => $anterior]))->assertSessionHasNoErrors();
    [$rascunho,$itemRasc] = abrirBancada($this);
    digitarBancada($this, $itemRasc, '5');
    [$novo,$item] = abrirBancada($this);
    digitarBancada($this, $item, '6');
    enviarBancada($this, $item);
    $this->get(route('lab.bancada.index', ['item' => $item]))->assertInertia(fn (Assert $p) => $p->where('selecionado.analitos.0.anterior.valor', '4'));
    $this->get(route('lab.liberacao.index', ['pedido' => $novo]))->assertInertia(fn (Assert $p) => $p->where('selecionado.resultados.0.anterior', '4')->where('selecionado.resultados.0.delta', true)->where('selecionado.resultados.0.percentual', 50));
});
it('impede não RT mesmo quando administrador e compartilha eRt falso', function (): void {
    [$pedido,$item] = abrirBancada($this);
    digitarBancada($this, $item, '4');
    enviarBancada($this, $item);
    DB::table('laboratorio_usuario')->where('user_id', $this->usuario->id)->update(['e_rt' => false]);
    $this->get(route('lab.liberacao.index'))->assertInertia(fn (Assert $p) => $p->where('eRt', false)->where('laboratorio.eRt', false));
    $this->post(route('lab.liberacao.liberar', ['pedido' => $pedido]))->assertSessionHasErrors(['form' => 'Só o responsável técnico libera laudos.']);
    expect(DB::table('laudos')->count())->toBe(0);
});
it('exige preenchimento completo e recusa parâmetro estranho e decimal inválido', function (): void {
    [$pedido,$item] = abrirBancada($this);
    $this->post(route('lab.bancada.conferir', ['item' => $item]))->assertSessionHasErrors('form');
    foreach (['1e2', 'NaN', '1.234'] as $valor) {
        $this->post(route('lab.bancada.salvar', ['item' => $item]), ['valores' => [$this->c['analito'] => ['valor' => $valor, 'texto' => null]]])->assertSessionHasErrors('form');
    }
    $this->post(route('lab.bancada.salvar', ['item' => $item]), ['valores' => [(string) Str::uuid7() => ['valor' => '4', 'texto' => null]]])->assertSessionHasErrors('form');
    expect(DB::table('resultados')->count())->toBe(0);
});
it('congela a faixa e classificação mesmo quando catálogo muda', function (): void {
    [$pedido,$item] = abrirBancada($this);
    digitarBancada($this, $item, '6');
    enviarBancada($this, $item);
    DB::table('faixas_referencia')->update(['maximo' => 7]);
    $this->get(route('lab.liberacao.index', ['pedido' => $pedido]))->assertInertia(fn (Assert $p) => $p->where('selecionado.resultados.0.referencia', '3 a 5')->where('selecionado.resultados.0.classificacao', 'alto'));
});
it('reabre para corrigir e exige novo aviso para a nova digitação', function (): void {
    [$pedido,$item] = abrirBancada($this);
    digitarBancada($this, $item, '9');
    enviarBancada($this, $item);
    avisarBancada($this, $pedido);
    $this->post(route('lab.bancada.reabrir', ['item' => $item]))->assertSessionHasNoErrors();
    digitarBancada($this, $item, '10');
    enviarBancada($this, $item);
    $this->post(route('lab.liberacao.liberar', ['pedido' => $pedido]))->assertSessionHasErrors('form');
    avisarBancada($this, $pedido);
    $this->post(route('lab.liberacao.liberar', ['pedido' => $pedido]))->assertSessionHasNoErrors();
});
it('aceita resultado qualitativo sem avaliação numérica', function (): void {
    [$pedido,$item] = abrirBancada($this);
    $this->post(route('lab.bancada.salvar', ['item' => $item]), ['valores' => [$this->c['analito'] => ['valor' => null, 'texto' => 'Não reagente']]])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('resultados', ['item_id' => $item, 'texto' => 'Não reagente', 'valor' => null, 'classificacao' => 'qualitativo']);
});
it('isola resultados laudos e avisos e retorna 404 para ids de outro laboratório', function (): void {
    [$pedido,$item] = abrirBancada($this);
    digitarBancada($this, $item, '9');
    enviarBancada($this, $item);
    avisarBancada($this, $pedido);
    $this->post(route('lab.liberacao.liberar', ['pedido' => $pedido]))->assertSessionHasNoErrors();
    $beta = Laboratorios::criar('Beta');
    app(ContextoLaboratorio::class)->executarComo($beta, function (): void {
        expect(ResultadoModel::query()->count())->toBe(0)->and(LaudoModel::query()->count())->toBe(0)->and(AvisoModel::query()->count())->toBe(0);
    });
    $this->actingAs(Laboratorios::usuarioEm($beta))->withSession(['laboratorio_id' => $beta]);
    $this->get(route('lab.bancada.index', ['item' => $item]))->assertNotFound();
    $this->get(route('lab.liberacao.index', ['pedido' => $pedido]))->assertNotFound();
    $this->post(route('lab.liberacao.liberar', ['pedido' => $pedido]))->assertNotFound();
    $this->post(route('lab.bancada.reabrir', ['item' => $item]))->assertNotFound();
});

it('só põe o pedido na fila com todos os itens ativos digitados e preserva cancelados', function (): void {
    $this->post(route('lab.pedidos.store'), [
        'paciente_id' => $this->c['animal'], 'solicitante_id' => $this->c['vinculo'], 'pagador' => 'responsavel',
        'prioridade' => 'rotina', 'exame_ids' => [$this->c['bioquimico'], $this->c['hemograma']],
    ])->assertSessionHasNoErrors();
    $pedido = DB::table('pedidos')->value('id');
    $item = DB::table('pedido_itens')->where('exame_id', $this->c['bioquimico'])->value('id');
    $outroItem = DB::table('pedido_itens')->where('exame_id', $this->c['hemograma'])->value('id');
    foreach (DB::table('amostras')->pluck('codigo') as $codigo) {
        $this->post(route('lab.amostras.receber'), ['codigo' => $codigo])->assertSessionHasNoErrors();
    }
    digitarBancada($this, $item, '4');
    enviarBancada($this, $item);
    $this->get(route('lab.liberacao.index'))->assertInertia(fn (Assert $p) => $p->has('fila', 0));
    $this->post(route('lab.liberacao.liberar', ['pedido' => $pedido]))->assertSessionHasErrors('form');
    DB::table('pedido_itens')->where('id', $outroItem)->update(['situacao' => 'cancelado']);
    $this->get(route('lab.liberacao.index'))->assertInertia(fn (Assert $p) => $p->has('fila', 1));
    $this->post(route('lab.liberacao.liberar', ['pedido' => $pedido]))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('pedido_itens', ['id' => $outroItem, 'situacao' => 'cancelado']);
});

it('numera laudos e códigos únicos por laboratório e impede duplicar a liberação', function (): void {
    foreach ([1, 2] as $numero) {
        [$pedido, $item] = abrirBancada($this);
        digitarBancada($this, $item, '4');
        enviarBancada($this, $item);
        $this->post(route('lab.liberacao.liberar', ['pedido' => $pedido]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('laudos', ['pedido_id' => $pedido, 'numero' => $numero]);
        $this->post(route('lab.liberacao.liberar', ['pedido' => $pedido]))->assertSessionHasErrors('form');
    }
    expect(DB::table('laudos')->distinct()->count('codigo_acesso'))->toBe(2);
    $this->lab = Laboratorios::criar('Beta');
    $this->usuario = Laboratorios::usuarioEm($this->lab);
    $this->c = CenarioDeBancada::criar($this->lab);
    DB::table('laboratorio_usuario')->where('user_id', $this->usuario->id)->update(['e_rt' => true]);
    $this->actingAs($this->usuario)->withSession(['laboratorio_id' => $this->lab]);
    [$pedido, $item] = abrirBancada($this);
    digitarBancada($this, $item, '4');
    enviarBancada($this, $item);
    $this->post(route('lab.liberacao.liberar', ['pedido' => $pedido]))->assertSessionHasNoErrors()->assertSessionHas('success', 'Laudo L-00001 liberado.');
});
