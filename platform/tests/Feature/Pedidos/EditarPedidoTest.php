<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\CenarioDeBancada;
use Tests\Support\Laboratorios;
use Vidra\Pedidos\Application\ParaBancada\ConsultarTrabalho;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;

beforeEach(function (): void {
    $this->lab = Laboratorios::criar();
    $this->usuario = Laboratorios::usuarioEm($this->lab);
    $this->c = CenarioDeBancada::criar($this->lab);
    $this->actingAs($this->usuario)->post(route('lab.pedidos.store'), ['paciente_id' => $this->c['animal'], 'solicitante_id' => $this->c['vinculo'], 'pagador' => 'clinica', 'clinica_id' => $this->c['clinica'], 'prioridade' => 'rotina', 'exame_ids' => [$this->c['bioquimico'], $this->c['hemograma']]])->assertSessionHasNoErrors();
    $this->pedido = DB::table('pedidos')->value('id');
    $this->item = DB::table('pedido_itens')->where('codigo', 'BIOQ')->value('id');
    $this->cancelarExameUrl = route('lab.pedidos.itens.cancelar', ['pedido' => $this->pedido, 'item' => $this->item]);
    $this->cancelarPedidoUrl = route('lab.pedidos.cancelar', ['pedido' => $this->pedido]);
    $this->incluirUrl = route('lab.pedidos.itens.store', ['pedido' => $this->pedido]);
    $this->novoExame = function (string $material = ' soro '): string {
        $id = (string) Str::uuid7();
        DB::table('exames')->insert(['id' => $id, 'laboratorio_id' => $this->lab, 'codigo' => 'NOVO', 'nome' => 'Novo exame', 'setor' => 'bioquimica', 'material' => $material, 'prazo_horas' => 24, 'preco_centavos' => 1005, 'ativo' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('analitos')->insert(['id' => (string) Str::uuid7(), 'laboratorio_id' => $this->lab, 'exame_id' => $id, 'nome' => 'Glicose', 'unidade' => 'mg/dL', 'casas_decimais' => 0, 'ordem' => 0, 'created_at' => now(), 'updated_at' => now()]);

        return $id;
    };
});

it('cancela exame com motivo e exibe autor e data no horário de São Paulo', function (): void {
    $this->travelTo(now()->setDate(2026, 11, 10)->setTime(15, 30));
    $this->post($this->cancelarExameUrl, ['motivo' => 'Clínica dispensou o exame'])->assertSessionHasNoErrors()->assertRedirect(route('lab.pedidos.show', ['pedido' => $this->pedido]));
    $this->assertDatabaseHas('pedido_itens', ['id' => $this->item, 'situacao' => 'cancelado', 'cancelado_por' => $this->usuario->id, 'cancelado_motivo' => 'Clínica dispensou o exame']);
    $this->assertDatabaseMissing('amostra_itens', ['item_id' => $this->item]);
    $this->get(route('lab.pedidos.show', ['pedido' => $this->pedido]))->assertInertia(fn (Assert $page) => $page
        ->where('pedido.itens.0.cancelamento.motivo', 'Clínica dispensou o exame')
        ->where('pedido.itens.0.cancelamento.por', $this->usuario->name)
        ->where('pedido.itens.0.cancelamento.em', '10/11/2026 12:30')
        ->where('pedido.totalCentavos', 4200)->where('pedido.etiquetas', 1)->has('pedido.amostras', 1));
    $this->get(route('lab.pedidos.etiquetas', ['pedido' => $this->pedido]))->assertDontSee('A-0001-1')->assertSee('A-0001-2');
    $this->get(route('lab.amostras.index'))->assertInertia(fn (Assert $page) => $page->has('aguardando', 1));
});

it('exige motivo em português sem alterar o pedido', function (string $acao): void {
    $url = $acao === 'exame' ? $this->cancelarExameUrl : $this->cancelarPedidoUrl;
    $this->post($url, [])->assertSessionHasErrors(['motivo' => 'Escreva o motivo do cancelamento.']);
    $this->assertDatabaseHas('pedido_itens', ['id' => $this->item, 'situacao' => 'aguardando_amostra', 'cancelado_motivo' => null]);
})->with(['exame', 'pedido']);

it('recusa motivo maior que quinhentos caracteres', function (): void {
    $this->post($this->cancelarPedidoUrl, ['motivo' => str_repeat('a', 501)])->assertSessionHasErrors(['motivo' => 'O motivo deve ter no máximo 500 caracteres.']);
});

it('não cancela o último exame ativo', function (): void {
    $this->post($this->cancelarExameUrl, ['motivo' => 'Dispensado'])->assertSessionHasNoErrors();
    $item = DB::table('pedido_itens')->where('codigo', 'HEMO')->value('id');
    $this->post(route('lab.pedidos.itens.cancelar', ['pedido' => $this->pedido, 'item' => $item]), ['motivo' => 'Dispensado'])->assertSessionHasErrors('form');
    $this->assertDatabaseHas('pedido_itens', ['id' => $item, 'situacao' => 'aguardando_amostra']);
});

it('cancela pedido e esvazia as amostras pendentes sem apagar seu histórico', function (): void {
    $this->post($this->cancelarPedidoUrl, ['motivo' => 'Paciente não fará a coleta'])->assertSessionHasNoErrors();
    expect(DB::table('pedido_itens')->where('situacao', 'cancelado')->count())->toBe(2)
        ->and(DB::table('amostra_itens')->count())->toBe(0)->and(DB::table('amostras')->count())->toBe(2);
    $this->get(route('lab.pedidos.show', ['pedido' => $this->pedido]))->assertInertia(fn (Assert $page) => $page
        ->where('pedido.situacao', 'cancelado')->where('pedido.totalCentavos', 0)
        ->where('pedido.podeCancelar', false)->where('pedido.podeIncluirExame', false)->has('pedido.amostras', 0));
    $this->get(route('lab.amostras.index'))->assertInertia(fn (Assert $page) => $page->has('aguardando', 0));
});

it('não cancela pedido que tem exame liberado nem inclui outro exame', function (): void {
    DB::table('pedido_itens')->where('id', $this->item)->update(['situacao' => 'liberado']);
    $this->post($this->cancelarPedidoUrl, ['motivo' => 'Dispensado'])->assertSessionHasErrors('form');
    $this->post($this->cancelarExameUrl, ['motivo' => 'Dispensado'])->assertSessionHasErrors('form');
    $this->post($this->incluirUrl, ['exame_id' => ($this->novoExame)()])->assertSessionHasErrors('form');
    expect(DB::table('pedido_itens')->whereNotNull('cancelado_em')->count())->toBe(0);
    $this->get(route('lab.pedidos.show', ['pedido' => $this->pedido]))->assertInertia(fn (Assert $page) => $page->where('pedido.podeCancelar', false)->where('pedido.podeIncluirExame', false));
});

it('inclui exame na amostra pendente de mesmo material com desconto e ordem corretos', function (): void {
    $id = ($this->novoExame)();
    $this->post($this->incluirUrl, ['exame_id' => $id])->assertSessionHasNoErrors()->assertRedirect(route('lab.pedidos.show', ['pedido' => $this->pedido]));
    $this->assertDatabaseHas('pedido_itens', ['exame_id' => $id, 'ordem' => 2, 'desconto_percentual' => 10, 'preco_centavos' => 905, 'situacao' => 'aguardando_amostra']);
    $item = DB::table('pedido_itens')->where('exame_id', $id)->value('id');
    $amostra = DB::table('amostras')->where('codigo', 'A-0001-1')->value('id');
    $this->assertDatabaseHas('amostra_itens', ['amostra_id' => $amostra, 'item_id' => $item]);
    expect(DB::table('amostras')->count())->toBe(2);
});

it('inclui material novo em nova amostra e avisa para imprimir etiqueta', function (): void {
    $id = ($this->novoExame)('Urina');
    $this->post($this->incluirUrl, ['exame_id' => $id])->assertSessionHasNoErrors()
        ->assertRedirect(route('lab.pedidos.show', ['pedido' => $this->pedido, 'nova_amostra' => 1]))
        ->assertSessionHas('success', 'Exame incluído. Imprima a etiqueta da nova amostra.');
    $this->assertDatabaseHas('amostras', ['pedido_id' => $this->pedido, 'codigo' => 'A-0001-3', 'material' => 'Urina']);
    $this->get(route('lab.pedidos.show', ['pedido' => $this->pedido, 'nova_amostra' => 1]))->assertInertia(fn (Assert $page) => $page->where('novaAmostra', true));
});

it('preserva amostra recebida ao cancelar exame e não devolve o cancelado para a bancada', function (): void {
    $amostra = DB::table('amostras')->where('codigo', 'A-0001-1')->value('id');
    $this->post(route('lab.amostras.receber'), ['codigo' => 'A-0001-1'])->assertSessionHasNoErrors();
    $this->post($this->cancelarExameUrl, ['motivo' => 'Dispensado'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('amostra_itens', ['amostra_id' => $amostra, 'item_id' => $this->item]);
    app(ContextoLaboratorio::class)->executarComo($this->lab, function (): void {
        $itens = app(ConsultarTrabalho::class)([$this->pedido]);
        expect(array_map(fn ($i) => $i->id, $itens))->not->toContain($this->item);
    });
    $this->post($this->incluirUrl, ['exame_id' => ($this->novoExame)()])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('amostras', ['codigo' => 'A-0001-3']);
});

it('permite incluir novamente um exame cancelado preservando seu histórico', function (): void {
    $this->post($this->cancelarExameUrl, ['motivo' => 'Dispensado'])->assertSessionHasNoErrors();
    $this->post($this->incluirUrl, ['exame_id' => $this->c['bioquimico']])->assertSessionHasNoErrors();
    expect(DB::table('pedido_itens')->where('exame_id', $this->c['bioquimico'])->count())->toBe(2)
        ->and(DB::table('amostras')->count())->toBe(2);
    $this->assertDatabaseHas('pedido_itens', ['id' => $this->item, 'situacao' => 'cancelado', 'cancelado_motivo' => 'Dispensado']);
});

it('recusa incluir exame repetido ou inativo ou com identificador inválido', function (): void {
    $this->post($this->incluirUrl, ['exame_id' => $this->c['bioquimico']])->assertSessionHasErrors('form');
    $id = ($this->novoExame)();
    DB::table('exames')->where('id', $id)->update(['ativo' => false]);
    $this->post($this->incluirUrl, ['exame_id' => $id])->assertSessionHasErrors('exame_ids');
    $this->post($this->incluirUrl, ['exame_id' => 'inválido'])->assertSessionHasErrors(['exame_id' => 'Escolha um exame válido.']);
    expect(DB::table('pedido_itens')->count())->toBe(2);
});

it('recusa alterações em pedido cancelado', function (): void {
    $this->post($this->cancelarPedidoUrl, ['motivo' => 'Dispensado'])->assertSessionHasNoErrors();
    $this->post($this->cancelarPedidoUrl, ['motivo' => 'Dispensado'])->assertSessionHasErrors('form');
    $this->post($this->cancelarExameUrl, ['motivo' => 'Dispensado'])->assertSessionHasErrors('form');
    $this->post($this->incluirUrl, ['exame_id' => ($this->novoExame)()])->assertSessionHasErrors('form');
});

it('devolve 404 ao tentar editar pedido de outro laboratório', function (string $acao): void {
    $this->actingAs(Laboratorios::usuario());
    $url = match ($acao) {
        'pedido' => $this->cancelarPedidoUrl, 'exame' => $this->cancelarExameUrl, default => $this->incluirUrl
    };
    $this->post($url, ['motivo' => 'Dispensado', 'exame_id' => $this->c['bioquimico']])->assertNotFound();
    expect(DB::table('pedido_itens')->where('situacao', 'cancelado')->count())->toBe(0);
})->with(['pedido', 'exame', 'incluir']);

it('bancada recebe 403 nas três alterações de pedido', function (string $acao): void {
    $this->actingAs(Laboratorios::usuarioEm($this->lab, 'bancada'));
    $url = match ($acao) {
        'pedido' => $this->cancelarPedidoUrl, 'exame' => $this->cancelarExameUrl, default => $this->incluirUrl
    };
    $this->post($url, ['motivo' => 'Dispensado', 'exame_id' => $this->c['bioquimico']])->assertForbidden();
})->with(['pedido', 'exame', 'incluir']);

it('não inclui nem oferece na busca um exame sem parâmetros, que nunca teria resultado', function (): void {
    $vazio = (string) Str::uuid7();
    DB::table('exames')->insert(['id' => $vazio, 'laboratorio_id' => $this->lab, 'codigo' => 'VAZIO', 'nome' => 'Exame sem parâmetros', 'setor' => 'bioquimica', 'material' => 'Soro', 'prazo_horas' => 24, 'preco_centavos' => 1000, 'ativo' => true, 'created_at' => now(), 'updated_at' => now()]);

    $this->post($this->incluirUrl, ['exame_id' => $vazio])->assertSessionHasErrors();
    $this->get(route('lab.pedidos.create', ['busca_exame' => 'sem parâmetros']))->assertInertia(fn ($p) => $p->where('exames', []));
    $this->assertDatabaseMissing('pedido_itens', ['exame_id' => $vazio]);
});
