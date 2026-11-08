<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\CenarioDeEntrega;
use Tests\Support\Laboratorios;
use Vidra\Laudos\Application\MontarLaudo\MontarLaudo;
use Vidra\Laudos\Infrastructure\FalhaAoGerarPdf;
use Vidra\Laudos\Infrastructure\HtmlDoLaudo;
use Vidra\Laudos\Infrastructure\Jobs\EntregarLaudo;
use Vidra\Laudos\Infrastructure\Mail\LaudoLiberadoMail;
use Vidra\Laudos\Infrastructure\Persistence\AcessoModel;
use Vidra\Laudos\Infrastructure\Persistence\EntregaModel;
use Vidra\Laudos\Infrastructure\Persistence\LaudoModel;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;

beforeEach(function (): void {
    Http::preventStrayRequests();
    $this->statusGotenberg = 200;
    Http::fake(fn () => Http::response($this->statusGotenberg === 200 ? '%PDF-1.7 teste' : 'Indisponível', $this->statusGotenberg, ['Content-Type' => 'application/pdf']));
    Mail::fake();
    Storage::fake('laudos');
    config()->set('vidra.urls.laudo', 'http://localhost/laudo');
    $this->c = CenarioDeEntrega::liberar($this);
    $this->job = Queue::pushed(EntregarLaudo::class)->first();
});
it('liberar despacha o job com laboratório e três tentativas', function (): void {
    Queue::assertPushed(EntregarLaudo::class, fn ($j) => $j->laboratorioId === $this->c['lab'] && $j->pedidoId === $this->c['pedido'] && $j->tries === 3);
});
it('gera PDF A4 guarda e envia aos destinatários com anexo', function (): void {
    CenarioDeEntrega::executar($this->job);
    $caminho = $this->c['lab'].'/'.$this->c['laudo'].'.pdf';
    Storage::disk('laudos')->assertExists($caminho);
    expect(Storage::disk('laudos')->get($caminho))->toBe('%PDF-1.7 teste');
    $this->assertDatabaseHas('laudos', ['id' => $this->c['laudo'], 'pdf_caminho' => $caminho, 'pdf_tentativas' => 1, 'pdf_erro' => null]);
    Mail::assertSent(LaudoLiberadoMail::class, 2);
    Mail::assertSent(LaudoLiberadoMail::class, fn ($m) => $m->hasTo('carla@exemplo.com.br') && $m->attachments()[0]->as === 'laudo-thor-L-00001.pdf' && str_contains($m->render(), $this->c['codigo']));
    Http::assertSent(fn ($r) => $r->hasFile('files', null, 'index.html') && $r->hasFile('paperWidth', '8.27') && $r->hasFile('printBackground', 'true'));
    $this->assertDatabaseCount('entregas_laudo', 2);
});
it('não envia ao responsável sem autorização nem ao solicitante com resumo diário', function (): void {
    DB::table('tutores')->where('id', $this->c['tutor'])->update(['email_autorizado_em' => null, 'email_autorizado_por' => null]);
    DB::table('laboratorio_veterinario')->where('id', $this->c['vinculo'])->update(['recebe_laudo' => 'resumo_diario']);
    Log::spy();
    CenarioDeEntrega::executar($this->job);
    Mail::assertNothingSent();
    Log::shouldHaveReceived('info')->once();
    $this->get(route('lab.pedidos.show', ['pedido' => $this->c['pedido']]))->assertInertia(fn (Assert $p) => $p->where('entrega.entregas.0', 'Solicitante com resumo diário: envio não realizado. Resumo diário ainda não disponível.'));
});
it('não envia ao responsável sem email mesmo com autorização', function (): void {
    DB::table('tutores')->where('id', $this->c['tutor'])->update(['email' => null]);
    CenarioDeEntrega::executar($this->job);
    Mail::assertSent(LaudoLiberadoMail::class, 1);
    Mail::assertNotSent(LaudoLiberadoMail::class, fn ($m) => $m->papel === 'tutor');
});
it('registra falha de geração e relança para a fila tentar novamente', function (): void {
    $this->statusGotenberg = 503;
    expect(fn () => CenarioDeEntrega::executar($this->job))->toThrow(FalhaAoGerarPdf::class);
    $this->assertDatabaseHas('laudos', ['id' => $this->c['laudo'], 'pdf_tentativas' => 1, 'pdf_erro' => 'O Gotenberg não gerou um PDF válido (HTTP 503).']);
    Mail::assertNothingSent();
    Storage::disk('laudos')->assertMissing($this->c['lab'].'/'.$this->c['laudo'].'.pdf');
});
it('o HTML contém resultados RT ressalva observação código e QR real', function (): void {
    DB::table('amostras')->where('pedido_id', $this->c['pedido'])->update(['situacao' => 'aceita_com_ressalva', 'ressalva' => 'levemente hemolisada']);
    $html = app(ContextoLaboratorio::class)->executarComo($this->c['lab'], fn () => app(HtmlDoLaudo::class)(app(MontarLaudo::class)($this->c['pedido'])));
    expect($html)->toContain('4,50', 'Potássio', 'Marina Alves', 'CRMV-SP 12345', $this->c['codigo'], 'data:image/svg+xml;base64,', 'levemente hemolisada', 'Conferido pelo RT.')->not->toContain('ICP-Brasil');
});
it('abre sem login com código normalizado registra acesso mínimo e restaura contexto', function (): void {
    auth()->logout();
    app(ContextoLaboratorio::class)->limpar();
    $this->withServerVariables(['REMOTE_ADDR' => '192.168.10.123'])->withHeader('User-Agent', str_repeat('a', 250))
        ->get('/laudo/'.strtolower(str_replace('-', '', $this->c['codigo'])))->assertOk()->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertInertia(fn (Assert $p) => $p->component('laudo/show')->where('laudo.paciente.nome', 'Thor')->where('laudo.pdf', null)->where('laudo.exames.0.resultados.0.valor', '4,50')->missing('laudo.destinatarios'));
    $this->assertDatabaseHas('acessos_laudo', ['laudo_id' => $this->c['laudo'], 'laboratorio_id' => $this->c['lab'], 'ip' => '192.168.10.0', 'user_agent' => str_repeat('a', 200)]);
    expect(app(ContextoLaboratorio::class)->definido())->toBeFalse();
});
it('código errado retorna mensagem 404 e décima primeira tentativa retorna 429', function (): void {
    auth()->logout();
    for ($i = 0; $i < 10; $i++) {
        $this->get('/laudo/ZZZZZZZZ')->assertNotFound()->assertInertia(fn (Assert $p) => $p->component('laudo/acesso')->where('erro', 'Não encontramos um laudo com este código. Confira as letras e os números.'));
    }
    $this->get('/laudo/ZZZZZZZZ')->assertStatus(429);
    $this->assertDatabaseCount('acessos_laudo', 0);
});
it('baixa PDF por código e pelo pedido com nome de arquivo e anexo', function (): void {
    CenarioDeEntrega::executar($this->job);
    $this->get(route('lab.pedidos.laudo.pdf', ['pedido' => $this->c['pedido']]))->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="laudo-thor-L-00001.pdf"')->assertContent('%PDF-1.7 teste');
    auth()->logout();
    $this->get('/laudo/'.$this->c['codigo'].'/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertContent('%PDF-1.7 teste');
});
it('isola as entregas os acessos e o job e não permite baixar ou reenviar de outro laboratório', function (): void {
    CenarioDeEntrega::executar($this->job);
    $this->get('/laudo/'.$this->c['codigo'])->assertOk();
    $beta = Laboratorios::criar('Beta');
    app(ContextoLaboratorio::class)->executarComo($beta, function (): void {
        expect(EntregaModel::query()->count())->toBe(0)->and(AcessoModel::query()->count())->toBe(0)->and(LaudoModel::query()->count())->toBe(0);
        CenarioDeEntrega::executar($this->job);
        expect(app(ContextoLaboratorio::class)->id())->not->toBe($this->c['lab']);
    });
    Mail::assertSent(LaudoLiberadoMail::class, 2);
    $this->actingAs(Laboratorios::usuarioEm($beta))->withSession(['laboratorio_id' => $beta]);
    $this->get(route('lab.pedidos.laudo.pdf', ['pedido' => $this->c['pedido']]))->assertNotFound();
    $this->post(route('lab.pedidos.laudo.reenviar', ['pedido' => $this->c['pedido']]))->assertNotFound();
});
it('reenvia só e-mails sem regenerar PDF e mostra entrega no pedido', function (): void {
    CenarioDeEntrega::executar($this->job);
    $this->get(route('lab.pedidos.show', ['pedido' => $this->c['pedido']]))->assertInertia(fn (Assert $p) => $p->where('entrega.pdfPronto', true)->has('entrega.entregas', 2)->where('entrega.whatsapp', fn ($url) => str_starts_with($url, 'https://wa.me/5519998124410?text=')));
    $this->post(route('lab.pedidos.laudo.reenviar', ['pedido' => $this->c['pedido']]))->assertSessionHasNoErrors();
    $novo = Queue::pushed(EntregarLaudo::class)->last();
    expect($novo->modo)->toBe('emails');
    CenarioDeEntrega::executar($novo);
    Http::assertSentCount(1);
    Mail::assertSent(LaudoLiberadoMail::class, 4);
});
it('sem PDF, o reenvio refaz a entrega completa para o laudo não ficar preso', function (): void {
    $this->get(route('lab.pedidos.show', ['pedido' => $this->c['pedido']]))->assertInertia(fn (Assert $p) => $p->where('entrega.pdfPronto', false));
    $this->post(route('lab.pedidos.laudo.reenviar', ['pedido' => $this->c['pedido']]))->assertSessionHasNoErrors();
    expect(Queue::pushed(EntregarLaudo::class)->last()->modo)->toBe('completa');
});

it('registra falha de email e repete só o destinatário que falhou na mesma entrega', function (): void {
    $falhar = true;
    $enviados = [];
    Mail::swap(Mockery::mock(Illuminate\Mail\MailManager::class));
    Mail::shouldReceive('to')->andReturnUsing(function (string $email) use (&$falhar, &$enviados) {
        $pendente = Mockery::mock(Illuminate\Mail\PendingMail::class);
        $pendente->shouldReceive('send')->once()->andReturnUsing(function () use ($email, &$falhar, &$enviados): void {
            if ($email === 'carla@exemplo.com.br' && $falhar) {
                throw new RuntimeException('SMTP indisponível');
            }
            $enviados[] = $email;
        });

        return $pendente;
    });
    expect(fn () => CenarioDeEntrega::executar($this->job))->toThrow(RuntimeException::class, 'SMTP indisponível');
    $this->assertDatabaseHas('entregas_laudo', ['destinatario' => 'carla@exemplo.com.br', 'situacao' => 'falhou', 'erro' => 'SMTP indisponível']);
    expect($enviados)->toHaveCount(1);
    $falhar = false;
    CenarioDeEntrega::executar($this->job);
    expect($enviados)->toHaveCount(2);
    $this->assertDatabaseCount('entregas_laudo', 3);
    Http::assertSentCount(1);
});
it('job de outro laboratório não encontra o pedido e restaura o contexto após exceção', function (): void {
    $contexto = app(ContextoLaboratorio::class);
    $contexto->limpar();
    $beta = Laboratorios::criar('Beta');
    expect(fn () => CenarioDeEntrega::executar(new EntregarLaudo($beta, $this->c['pedido'])))->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
    expect($contexto->definido())->toBeFalse();
    Mail::assertNothingSent();
    Http::assertNothingSent();
});

it('mantém o anterior cronológico quando um terceiro laudo é liberado depois', function (): void {
    $r = DB::table('resultados')->where('pedido_id', $this->c['pedido'])->first();
    $exame = DB::table('pedido_itens')->where('pedido_id', $this->c['pedido'])->value('exame_id');
    $novo = function (string $valor) use ($r, $exame): string {
        $this->post(route('lab.pedidos.store'), ['paciente_id' => $r->paciente_id, 'solicitante_id' => $this->c['vinculo'], 'pagador' => 'responsavel', 'prioridade' => 'rotina', 'exame_ids' => [$exame]])->assertSessionHasNoErrors();
        $pedido = DB::table('pedidos')->where('laboratorio_id', $this->c['lab'])->orderByDesc('protocolo')->value('id');
        $item = DB::table('pedido_itens')->where('pedido_id', $pedido)->value('id');
        $amostra = DB::table('amostras')->where('pedido_id', $pedido)->value('codigo');
        $this->post(route('lab.amostras.receber'), ['codigo' => $amostra])->assertSessionHasNoErrors();
        $this->post(route('lab.bancada.salvar', ['item' => $item]), ['valores' => [$r->analito_id => ['valor' => $valor, 'texto' => null]]])->assertSessionHasNoErrors();
        $this->post(route('lab.bancada.conferir', ['item' => $item]))->assertSessionHasNoErrors();
        $this->post(route('lab.liberacao.liberar', ['pedido' => $pedido]))->assertSessionHasNoErrors();

        return $pedido;
    };
    // A liberação usa relógio nativo; as datas persistidas fixam a ordem histórica do cenário.
    DB::table('laudos')->where('id', $this->c['laudo'])->update(['liberado_em' => '2026-11-06 12:00:00.000000']);
    $segundo = $novo('5,00');
    DB::table('laudos')->where('pedido_id', $segundo)->update(['liberado_em' => '2026-11-07 12:00:00.000000']);
    $terceiro = $novo('6,00');
    DB::table('laudos')->where('pedido_id', $terceiro)->update(['liberado_em' => '2026-11-08 12:00:00.000000']);
    $c = app(ContextoLaboratorio::class)->executarComo($this->c['lab'], fn () => app(MontarLaudo::class)($segundo));
    expect($c->exames[0]['resultados'][0]['anterior'])->toBe('4,50')->and($c->exames[0]['resultados'][0]['anteriorEm'])->toBe('06/11/2026');
});
