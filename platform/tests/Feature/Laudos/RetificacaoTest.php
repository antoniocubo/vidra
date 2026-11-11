<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\CenarioDeEntrega;
use Tests\Support\Laboratorios;
use Vidra\Laudos\Application\MontarLaudo\MontarLaudo;
use Vidra\Laudos\Infrastructure\HtmlDoLaudo;
use Vidra\Laudos\Infrastructure\Jobs\EntregarLaudo;
use Vidra\Laudos\Infrastructure\Mail\LaudoLiberadoMail;
use Vidra\Laudos\Infrastructure\Persistence\VersaoLaudoModel;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;

beforeEach(function (): void {
    Http::preventStrayRequests();
    Http::fake(fn () => Http::response('%PDF-1.7 versão', 200, ['Content-Type' => 'application/pdf']));
    Mail::fake();
    Storage::fake('laudos');
    $this->c = CenarioDeEntrega::liberar($this);
});

it('RT reabre com motivo e o pedido volta à fila sem mudar a versão publicada', function (): void {
    $this->post(route('lab.pedidos.laudo.retificar', ['pedido' => $this->c['pedido']]), ['motivo' => 'Correção do potássio'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('laudos', ['id' => $this->c['laudo'], 'versao' => 1, 'reaberto_motivo' => 'Correção do potássio']);
    $this->assertDatabaseHas('pedido_itens', ['pedido_id' => $this->c['pedido'], 'situacao' => 'resultado_digitado']);
    $this->get(route('lab.liberacao.index'))->assertInertia(fn (Assert $p) => $p->where('fila.0.retificacao', 'Correção do potássio')->where('selecionado.proximaVersao', 2));
});

it('recusa retificação por quem não é RT sem alterar o pedido', function (): void {
    DB::table('laboratorio_usuario')->where('laboratorio_id', $this->c['lab'])->update(['e_rt' => false]);
    $this->post(route('lab.pedidos.laudo.retificar', ['pedido' => $this->c['pedido']]), ['motivo' => 'Correção'])->assertSessionHasErrors();
    $this->assertDatabaseHas('pedido_itens', ['pedido_id' => $this->c['pedido'], 'situacao' => 'liberado']);
    $this->assertDatabaseHas('laudos', ['id' => $this->c['laudo'], 'reaberto_motivo' => null]);
});

it('exige motivo em português e limita a quinhentos caracteres', function (): void {
    $url = route('lab.pedidos.laudo.retificar', ['pedido' => $this->c['pedido']]);
    $this->post($url, ['motivo' => ' '])->assertSessionHasErrors(['motivo' => 'Diga o motivo da retificação. Ele sai impresso no laudo.']);
    $this->post($url, ['motivo' => str_repeat('a', 501)])->assertSessionHasErrors(['motivo' => 'Resuma o motivo da retificação em até 500 caracteres.']);
});

it('não reabre duas vezes nem libera novamente sem retificação', function (): void {
    $this->post(route('lab.liberacao.liberar', ['pedido' => $this->c['pedido']]))->assertSessionHasErrors();
    $url = route('lab.pedidos.laudo.retificar', ['pedido' => $this->c['pedido']]);
    $this->post($url, ['motivo' => 'Correção'])->assertSessionHasNoErrors();
    $this->post($url, ['motivo' => 'Outra correção'])->assertSessionHasErrors();
    $this->assertDatabaseCount('versoes_laudo', 1);
});

it('corrige na bancada libera v2 mantém o código e preserva PDF e resultados assinados da v1', function (): void {
    CenarioDeEntrega::executar(Queue::pushed(EntregarLaudo::class)->first());
    $v1 = DB::table('versoes_laudo')->where('laudo_id', $this->c['laudo'])->first();
    $this->post(route('lab.pedidos.laudo.retificar', ['pedido' => $this->c['pedido']]), ['motivo' => 'Correção do potássio'])->assertSessionHasNoErrors();
    $r = DB::table('resultados')->where('pedido_id', $this->c['pedido'])->first();
    $this->post(route('lab.bancada.reabrir', ['item' => $r->item_id]))->assertSessionHasNoErrors();
    $this->post(route('lab.bancada.salvar', ['item' => $r->item_id]), ['valores' => [$r->analito_id => ['valor' => '4,80', 'texto' => null]]])->assertSessionHasNoErrors();
    $this->get('/laudo/'.$this->c['codigo'])->assertInertia(fn (Assert $p) => $p->where('laudo.exames.0.resultados.0.valor', '4,50')->where('laudo.versao', 1));
    $this->post(route('lab.bancada.conferir', ['item' => $r->item_id]))->assertSessionHasNoErrors();
    $this->post(route('lab.liberacao.liberar', ['pedido' => $this->c['pedido']]))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('laudos', ['id' => $this->c['laudo'], 'codigo_acesso' => $this->c['codigo'], 'versao' => 2, 'reaberto_motivo' => null]);
    CenarioDeEntrega::executar(Queue::pushed(EntregarLaudo::class)->last());
    $v2 = DB::table('versoes_laudo')->where('laudo_id', $this->c['laudo'])->where('versao', 2)->first();
    expect($v2->pdf_caminho)->not->toBe($v1->pdf_caminho);
    expect(DB::table('versoes_laudo')->where('id', $v1->id)->first())->toEqual($v1);
    Storage::disk('laudos')->assertExists($v1->pdf_caminho);
    Storage::disk('laudos')->assertExists($v2->pdf_caminho);
    $html = app(ContextoLaboratorio::class)->executarComo($this->c['lab'], fn () => app(HtmlDoLaudo::class)(app(MontarLaudo::class)($this->c['pedido'])));
    expect($html)->toContain('Laudo retificado em', 'Correção do potássio', 'L-00001 v2', '4,80');
    $this->get('/laudo/'.$this->c['codigo'])->assertInertia(fn (Assert $p) => $p->where('laudo.versao', 2)->where('laudo.versoes.0.versao', 1)->where('laudo.exames.0.resultados.0.valor', '4,80'));
    $this->get('/laudo/'.$this->c['codigo'].'/versoes/1.pdf')->assertOk()->assertContent('%PDF-1.7 versão');
    $this->get(route('lab.pedidos.laudo.versao.pdf', ['pedido' => $this->c['pedido'], 'versao' => 1]))->assertOk();
    $this->get('/laudo/'.$this->c['codigo'].'/versoes/99.pdf')->assertNotFound();
});

it('reentrega a quem já recebeu mesmo com preferência alterada sem duplicar endereço', function (): void {
    CenarioDeEntrega::executar(Queue::pushed(EntregarLaudo::class)->first());
    DB::table('laboratorio_veterinario')->where('id', $this->c['vinculo'])->update(['recebe_laudo' => 'resumo_diario']);
    $this->post(route('lab.pedidos.laudo.retificar', ['pedido' => $this->c['pedido']]), ['motivo' => 'Correção'])->assertSessionHasNoErrors();
    $this->post(route('lab.liberacao.liberar', ['pedido' => $this->c['pedido']]))->assertSessionHasNoErrors();
    $job = Queue::pushed(EntregarLaudo::class)->last();
    CenarioDeEntrega::executar($job);
    CenarioDeEntrega::executar($job);
    Mail::assertSent(LaudoLiberadoMail::class, 4);
    Mail::assertSent(LaudoLiberadoMail::class, fn ($m) => $m->laudo->versao === 2 && str_contains($m->envelope()->subject, 'Laudo retificado') && str_contains($m->render(), 'Correção'));
});

it('isola versões por laboratório e impede baixar versão alheia pela área autenticada', function (): void {
    $beta = Laboratorios::criar('Beta');
    app(ContextoLaboratorio::class)->executarComo($beta, function (): void {
        expect(VersaoLaudoModel::query()->count())->toBe(0);
        expect(app(Vidra\Laudos\Domain\LaudoRepository::class)->versoes($this->c['laudo']))->toBe([]);
    });
    $this->actingAs(Laboratorios::usuarioEm($beta))->withSession(['laboratorio_id' => $beta]);
    $this->get(route('lab.pedidos.laudo.versao.pdf', ['pedido' => $this->c['pedido'], 'versao' => 1]))->assertNotFound();
    $this->post(route('lab.pedidos.laudo.retificar', ['pedido' => $this->c['pedido']]), ['motivo' => 'Correção'])->assertNotFound();
});

it('job atrasado preserva o PDF anterior sem substituir a versão atual nem reenviar versão antiga', function (): void {
    $antigo = Queue::pushed(EntregarLaudo::class)->first();
    $this->post(route('lab.pedidos.laudo.retificar', ['pedido' => $this->c['pedido']]), ['motivo' => 'Correção'])->assertSessionHasNoErrors();
    $this->post(route('lab.liberacao.liberar', ['pedido' => $this->c['pedido']]))->assertSessionHasNoErrors();
    CenarioDeEntrega::executar(Queue::pushed(EntregarLaudo::class)->last());
    $atual = DB::table('laudos')->where('id', $this->c['laudo'])->value('pdf_caminho');
    CenarioDeEntrega::executar($antigo);
    Mail::assertSent(LaudoLiberadoMail::class, 2);
    $this->assertDatabaseHas('laudos', ['id' => $this->c['laudo'], 'versao' => 2, 'pdf_caminho' => $atual]);
    $this->get('/laudo/'.$this->c['codigo'].'/versoes/1.pdf')->assertOk();
});

it('não deixa cancelar nem incluir exame num pedido com laudo em retificação', function (): void {
    $pedido = $this->c['pedido'];
    $this->post(route('lab.pedidos.laudo.retificar', $pedido), ['motivo' => 'Valor digitado errado'])->assertSessionHasNoErrors();

    $this->post(route('lab.pedidos.cancelar', $pedido), ['motivo' => 'Tutor desistiu'])->assertSessionHasErrors();
    $this->get(route('lab.pedidos.show', $pedido))->assertInertia(fn ($p) => $p->where('pedido.podeCancelar', false)->where('pedido.podeIncluirExame', false));
});
