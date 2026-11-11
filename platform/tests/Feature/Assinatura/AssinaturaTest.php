<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\CenarioDeFatura;
use Tests\Support\Laboratorios;
use Tests\Support\PedidosDaAssinaturaFixos;
use Vidra\Acesso\Domain\Assinatura\PedidosDoMes;
use Vidra\Acesso\Infrastructure\Mail\AvisoDeAssinaturaMail;

function lab(): Illuminate\Database\Query\Builder
{
    return DB::connection(config()->string('vidra.conexao_sistema'))->table('laboratorios');
}

/** Põe o fim do teste daqui a tantos dias (negativo = já terminou há tantos dias). */
function testeTerminaEmDias(string $lab, int $dias): void
{
    lab()->where('id', $lab)->update(['teste_termina_em' => now()->addDays($dias)->addMinutes(30)]);
}

beforeEach(function (): void {
    config(['services.asaas.webhook_token' => 'segredo', 'services.asaas.chave' => 'chave-teste', 'services.asaas.ambiente' => 'sandbox']);
    $this->lab = Laboratorios::criar();
    $this->admin = Laboratorios::usuarioEm($this->lab);
});

function evento(string $tipo = 'PAYMENT_RECEIVED', string $id = 'pay_1'): array
{
    return ['event' => $tipo, 'payment' => ['id' => $id, 'customer' => 'cus_1', 'value' => 290.0, 'dueDate' => '2026-12-10', 'invoiceUrl' => 'https://asaas.test/i/1']];
}

it('recusa o webhook com token errado ou ausente', function (): void {
    $this->postJson('/webhooks/asaas', evento(), ['asaas-access-token' => 'outro'])->assertUnauthorized();
    $this->postJson('/webhooks/asaas', evento())->assertUnauthorized();
});

it('pagamento confirmado estende o pago_ate e reenvio do mesmo evento não estende de novo', function (): void {
    lab()->where('id', $this->lab)->update(['asaas_cliente_id' => 'cus_1', 'teste_termina_em' => '2026-12-10 03:00:00']);
    $cabecalho = ['asaas-access-token' => 'segredo'];

    $this->postJson('/webhooks/asaas', evento(), $cabecalho)->assertOk();
    $this->postJson('/webhooks/asaas', evento(), $cabecalho)->assertOk();
    $this->postJson('/webhooks/asaas', evento('PAYMENT_CONFIRMED'), $cabecalho)->assertOk();

    expect(substr((string) lab()->where('id', $this->lab)->value('pago_ate'), 0, 10))->toBe('2027-01-10')
        ->and(DB::table('cobrancas_vidra')->where('laboratorio_id', $this->lab)->where('situacao', 'paga')->count())->toBe(1);
});

it('marca a cobrança como vencida no PAYMENT_OVERDUE e ignora os outros eventos', function (): void {
    lab()->where('id', $this->lab)->update(['asaas_cliente_id' => 'cus_1']);
    $cabecalho = ['asaas-access-token' => 'segredo'];

    $this->postJson('/webhooks/asaas', evento('PAYMENT_OVERDUE'), $cabecalho)->assertOk();
    $this->postJson('/webhooks/asaas', evento('PAYMENT_DELETED', 'pay_2'), $cabecalho)->assertOk();

    expect(DB::table('cobrancas_vidra')->pluck('situacao', 'asaas_pagamento_id')->all())->toBe(['pay_1' => 'vencida']);
});

it('só consulta bloqueia pedido novo, mas não a lista de pedidos', function (): void {
    testeTerminaEmDias($this->lab, -10);
    $this->actingAs($this->admin)->withSession(['laboratorio_id' => $this->lab]);

    $this->get('/pedidos/novo')->assertRedirect('/assinatura')->assertSessionHas('success', 'Seu teste terminou. Escolha um plano para voltar a abrir pedidos.');
    $this->post('/pedidos', [])->assertRedirect('/assinatura');
    $this->get('/pedidos')->assertOk();
});

it('manda quem não é admin de volta para a lista de pedidos quando está só em consulta', function (): void {
    testeTerminaEmDias($this->lab, -10);
    $recepcao = Laboratorios::usuarioEm($this->lab, 'recepcao');
    $this->actingAs($recepcao)->withSession(['laboratorio_id' => $this->lab]);

    $this->get('/pedidos/novo')->assertRedirect('/pedidos');
});

it('mostra a faixa de aviso nos últimos cinco dias do teste e não antes', function (): void {
    $this->actingAs($this->admin)->withSession(['laboratorio_id' => $this->lab]);

    $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('assinatura.deveAvisar', false)->where('assinatura.situacao', 'em_teste'));

    testeTerminaEmDias($this->lab, 3);
    $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('assinatura.deveAvisar', true)->where('assinatura.diasRestantes', 3));
});

it('abre a tela de plano e cobrança só para o administrador', function (): void {
    $this->actingAs($this->admin)->withSession(['laboratorio_id' => $this->lab])->get('/assinatura')
        ->assertOk()->assertInertia(fn (Assert $page) => $page->component('lab/assinatura/index')->where('situacao', 'em_teste')->has('planos', 3));

    foreach (['recepcao', 'rt', 'bancada'] as $papel) {
        $outro = Laboratorios::usuarioEm($this->lab, $papel);
        $this->actingAs($outro)->withSession(['laboratorio_id' => $this->lab])->get('/assinatura')->assertForbidden();
    }
});

it('um laboratório não vê as cobranças do outro', function (): void {
    $beta = Laboratorios::criar('Laboratório Beta');
    DB::table('cobrancas_vidra')->insert(['id' => (string) Str::uuid7(), 'laboratorio_id' => $beta, 'asaas_pagamento_id' => 'pay_beta', 'tipo' => 'mensalidade', 'valor_centavos' => 100, 'situacao' => 'pendente', 'vencimento' => '2026-12-10', 'created_at' => now(), 'updated_at' => now()]);

    $this->actingAs($this->admin)->withSession(['laboratorio_id' => $this->lab])->get('/assinatura')
        ->assertInertia(fn (Assert $page) => $page->has('cobrancas', 0)->where('linkPendente', null));
});

it('assinar chama o Asaas com cliente, assinatura e a primeira cobrança', function (): void {
    Http::fake([
        '*/customers' => Http::response(['id' => 'cus_9']),
        '*/subscriptions' => Http::response(['id' => 'sub_9']),
        '*/subscriptions/sub_9/payments' => Http::response(['data' => [['id' => 'pay_9', 'invoiceUrl' => 'https://asaas.test/i/9', 'value' => 590.0, 'dueDate' => '2026-12-10']]]),
    ]);
    $this->actingAs($this->admin)->withSession(['laboratorio_id' => $this->lab]);

    $this->post('/assinatura', ['plano' => 'laboratorio', 'cobranca' => 'mensal'])->assertRedirect('/assinatura');

    Http::assertSent(fn ($r) => $r->url() === 'https://sandbox.asaas.com/api/v3/customers' && $r->header('access_token') === ['chave-teste']
        && $r['name'] === 'Laboratório Alfa' && $r['email'] === $this->admin->email && $r['externalReference'] === $this->lab);
    Http::assertSent(fn ($r) => $r->url() === 'https://sandbox.asaas.com/api/v3/subscriptions' && $r['customer'] === 'cus_9' && $r['billingType'] === 'UNDEFINED'
        && $r['value'] === 590 && $r['cycle'] === 'MONTHLY' && $r['nextDueDate'] === (new DateTimeImmutable((string) lab()->where('id', $this->lab)->value('teste_termina_em'), new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('Y-m-d'));
    $row = lab()->where('id', $this->lab)->first();
    expect($row->asaas_cliente_id)->toBe('cus_9')->and($row->asaas_assinatura_id)->toBe('sub_9')->and($row->plano)->toBe('laboratorio')
        ->and(DB::table('cobrancas_vidra')->where('asaas_pagamento_id', 'pay_9')->value('link_pagamento'))->toBe('https://asaas.test/i/9');
});

it('mostra a mensagem amigável quando o Asaas recusa a assinatura', function (): void {
    Http::fake(['*/customers' => Http::response(['errors' => [['description' => 'CNPJ inválido']]], 400)]);
    $this->actingAs($this->admin)->withSession(['laboratorio_id' => $this->lab]);

    $this->post('/assinatura', ['plano' => 'bancada', 'cobranca' => 'mensal'])->assertSessionHasErrors(['form' => 'O serviço de cobrança recusou o pedido: CNPJ inválido']);
});

it('cobra o excedente do mês anterior uma vez só', function (): void {
    lab()->where('id', $this->lab)->update(['plano' => 'bancada', 'asaas_cliente_id' => 'cus_1', 'pago_ate' => now()->addDays(20)]);
    $this->app->instance(PedidosDoMes::class, new PedidosDaAssinaturaFixos(640));
    Http::fake(['*/payments' => Http::response(['id' => 'pay_ex', 'invoiceUrl' => 'https://asaas.test/i/ex', 'value' => 10.0, 'dueDate' => '2026-12-09'])]);

    $this->artisan('vidra:cobrar-excedentes', ['--competencia' => '2026-11'])->assertSuccessful();
    $this->artisan('vidra:cobrar-excedentes', ['--competencia' => '2026-11'])->assertSuccessful();

    Http::assertSentCount(1);
    Http::assertSent(fn ($r) => $r['value'] === 10 && $r['description'] === 'Excedente de 40 pedidos em 11/2026' && $r['customer'] === 'cus_1');
    expect(DB::table('cobrancas_vidra')->where('tipo', 'excedente')->where('competencia', '2026-11')->count())->toBe(1);
});

it('não cobra excedente dentro do limite do plano', function (): void {
    lab()->where('id', $this->lab)->update(['plano' => 'bancada', 'asaas_cliente_id' => 'cus_1', 'pago_ate' => now()->addDays(20)]);
    $this->app->instance(PedidosDoMes::class, new PedidosDaAssinaturaFixos(600));
    Http::fake();

    $this->artisan('vidra:cobrar-excedentes', ['--competencia' => '2026-11'])->assertSuccessful();

    Http::assertNothingSent();
});

it('conta os pedidos do mês local, sem os cancelados por inteiro e sem os de outro laboratório', function (): void {
    $beta = Laboratorios::criar('Laboratório Beta');
    $c = ['animal' => (string) Str::uuid7(), 'vinculo' => (string) Str::uuid7(), 'clinica' => (string) Str::uuid7()];
    CenarioDeFatura::pedido($this->lab, $c, 1, 'clinica', null, [9000, 5000]);
    CenarioDeFatura::pedido($this->lab, $c, 2, 'clinica', null, [9000, 5000], ['Exame 1']); // um item vivo: conta
    CenarioDeFatura::pedido($this->lab, $c, 3, 'clinica', null, [9000], ['Exame 1']); // tudo cancelado: não conta
    CenarioDeFatura::pedido($beta, $c, 1, 'clinica', null, [9000]);

    $contar = app(PedidosDoMes::class);

    expect($contar->contar($this->lab, 2026, 11))->toBe(2)
        ->and($contar->contar($this->lab, 2026, 10))->toBe(0)
        ->and($contar->contar($beta, 2026, 11))->toBe(1);
});

it('avisa o vencimento por e-mail no máximo uma vez por dia', function (): void {
    Mail::fake();
    testeTerminaEmDias($this->lab, 3);

    $this->artisan('vidra:avisar-vencimento')->assertSuccessful();
    $this->artisan('vidra:avisar-vencimento')->assertSuccessful();

    Mail::assertSent(AvisoDeAssinaturaMail::class, 1);
    Mail::assertSent(AvisoDeAssinaturaMail::class, fn (AvisoDeAssinaturaMail $m) => $m->hasTo($this->admin->email) && $m->diasRestantes === 3);
});

it('não avisa quem ainda tem muito tempo de teste', function (): void {
    Mail::fake();

    $this->artisan('vidra:avisar-vencimento')->assertSuccessful();

    Mail::assertNothingSent();
});
