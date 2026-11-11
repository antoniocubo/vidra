<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\CenarioDeFatura;
use Tests\Support\CenarioDePedidos;
use Tests\Support\Laboratorios;
use Vidra\Financeiro\Infrastructure\Jobs\EnviarFaturaPorEmail;
use Vidra\Financeiro\Infrastructure\Mail\FaturaMail;
use Vidra\Financeiro\Infrastructure\Persistence\FaturaModel;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;

beforeEach(function (): void {
    Queue::fake();
    Mail::fake();
    Storage::fake('laudos');
    Http::preventStrayRequests();
    Http::fake(fn () => Http::response('%PDF-1.7 teste', 200, ['Content-Type' => 'application/pdf']));
    $this->lab = Laboratorios::criar();
    $this->admin = Laboratorios::usuarioEm($this->lab);
    $this->c = CenarioDePedidos::criar($this->lab);
    // Clínica fecha dia 5 e vence dia 20; o veterinário usa as regras fixas (dia 1 e dia 10).
    DB::table('clinicas')->where('id', $this->c['clinica'])->update(['dia_fechamento' => 5, 'dia_vencimento' => 20]);
});

function fecharNoDia(string $dia): void
{
    test()->artisan('vidra:fechar-faturas', ['--dia' => $dia])->assertSuccessful();
}

it('fecha a fatura da clínica no dia certo e não em outro', function (): void {
    CenarioDeFatura::pedido($this->lab, $this->c, 1);

    fecharNoDia('2026-12-04');
    expect(DB::table('faturas')->count())->toBe(0);

    fecharNoDia('2026-12-05');
    $fatura = DB::table('faturas')->first();
    expect($fatura->pagador_nome)->toBe('Clínica Patas')
        ->and($fatura->competencia)->toBe('2026-11')
        ->and($fatura->numero)->toBe(1)
        ->and($fatura->vencimento)->toBe('2026-12-20')
        ->and($fatura->pagador_email)->toBe('financeiro@patas.com.br')
        ->and(DB::table('linhas_fatura')->where('fatura_id', $fatura->id)->first())->toMatchObject(['protocolo' => 'P-00001', 'paciente' => 'Thor', 'valor_centavos' => 9000]);
    Queue::assertPushed(EnviarFaturaPorEmail::class, fn ($j) => $j->laboratorioId === $this->lab && $j->faturaId === $fatura->id);
});

it('fatura o veterinário que paga por conta própria no dia 1', function (): void {
    DB::table('laboratorio_veterinario')->where('id', $this->c['vinculo'])->update(['quem_paga' => 'veterinario']);
    CenarioDeFatura::pedido($this->lab, $this->c, 1, pagador: 'solicitante');

    fecharNoDia('2026-12-01');

    expect(DB::table('faturas')->first())->toMatchObject(['pagador_tipo' => 'solicitante', 'pagador_id' => $this->c['vinculo'], 'vencimento' => '2026-12-10']);
});

it('não fatura pedido do tutor, cancelado, sem laudo ou de outra competência', function (): void {
    CenarioDeFatura::pedido($this->lab, $this->c, 1);
    CenarioDeFatura::pedido($this->lab, $this->c, 2, pagador: 'responsavel');
    CenarioDeFatura::pedido($this->lab, $this->c, 3, precos: [5000], cancelados: ['Exame 1']);
    CenarioDeFatura::pedido($this->lab, $this->c, 4, liberadoEm: null);
    CenarioDeFatura::pedido($this->lab, $this->c, 5, liberadoEm: '2026-10-20 15:00:00');

    fecharNoDia('2026-12-05');

    expect(DB::table('linhas_fatura')->pluck('protocolo')->all())->toBe(['P-00001']);
});

it('usa o preço gravado no item, com desconto, e ignora o exame cancelado', function (): void {
    CenarioDeFatura::pedido($this->lab, $this->c, 1, precos: [9000, 4200, 3000], cancelados: ['Exame 3']);

    fecharNoDia('2026-12-05');

    $linha = DB::table('linhas_fatura')->first();
    expect($linha->valor_centavos)->toBe(13200)->and($linha->exames)->toBe('Exame 1, Exame 2');
});

it('rodar duas vezes não duplica a fatura', function (): void {
    CenarioDeFatura::pedido($this->lab, $this->c, 1);

    fecharNoDia('2026-12-05');
    fecharNoDia('2026-12-05');

    expect(DB::table('faturas')->count())->toBe(1)->and(DB::table('linhas_fatura')->count())->toBe(1);
    Queue::assertPushed(EnviarFaturaPorEmail::class, 1);
});

it('manda o PDF por e-mail ao financeiro da clínica e marca como enviada', function (): void {
    CenarioDeFatura::pedido($this->lab, $this->c, 1);
    fecharNoDia('2026-12-05');
    $job = Queue::pushed(EnviarFaturaPorEmail::class)->first();

    CenarioDeFatura::enviar($job);

    Mail::assertSent(FaturaMail::class, fn ($m) => $m->hasTo('financeiro@patas.com.br') && $m->attachments()[0]->as === 'fatura-F-00001.pdf' && str_contains($m->render(), 'R$ 90,00'));
    $fatura = DB::table('faturas')->first();
    expect($fatura->enviada_em)->not->toBeNull()->and($fatura->pdf_caminho)->toBe("faturas/{$this->lab}/{$fatura->id}.pdf");
    Storage::disk('laudos')->assertExists($fatura->pdf_caminho);
    Http::assertSent(fn ($r) => $r->hasFile('files', null, 'index.html'));
});

it('reenviar manda de novo sem mudar a situação', function (): void {
    CenarioDeFatura::pedido($this->lab, $this->c, 1);
    fecharNoDia('2026-12-05');
    CenarioDeFatura::enviar(Queue::pushed(EnviarFaturaPorEmail::class)->first());
    $enviadaEm = DB::table('faturas')->value('enviada_em');
    $id = DB::table('faturas')->value('id');

    $this->actingAs($this->admin)->withSession(['laboratorio_id' => $this->lab])->post(route('lab.financeiro.reenviar', ['fatura' => $id]))->assertSessionHasNoErrors();
    CenarioDeFatura::enviar(Queue::pushed(EnviarFaturaPorEmail::class)->last());

    Mail::assertSent(FaturaMail::class, 2);
    expect(DB::table('faturas')->value('enviada_em'))->toBe($enviadaEm);
});

it('baixa manual só depois do envio e uma vez só', function (): void {
    CenarioDeFatura::pedido($this->lab, $this->c, 1);
    fecharNoDia('2026-12-05');
    $id = DB::table('faturas')->value('id');
    $this->actingAs($this->admin)->withSession(['laboratorio_id' => $this->lab]);

    $this->post(route('lab.financeiro.pagamento', ['fatura' => $id]))->assertSessionHasErrors(['form' => 'Envie a fatura antes de registrar o pagamento.']);

    CenarioDeFatura::enviar(Queue::pushed(EnviarFaturaPorEmail::class)->first());
    $this->post(route('lab.financeiro.pagamento', ['fatura' => $id]))->assertSessionHasNoErrors();
    expect(DB::table('faturas')->first())->toMatchObject(['paga_por' => $this->admin->id])->and(DB::table('faturas')->value('paga_em'))->not->toBeNull();

    $this->post(route('lab.financeiro.pagamento', ['fatura' => $id]))->assertSessionHasErrors(['form' => 'Esta fatura já está paga.']);
});

it('concede crédito em reais com vírgula e recusa o que passa do total', function (): void {
    CenarioDeFatura::pedido($this->lab, $this->c, 1);
    fecharNoDia('2026-12-05');
    CenarioDeFatura::enviar(Queue::pushed(EnviarFaturaPorEmail::class)->first());
    $id = DB::table('faturas')->value('id');
    $this->actingAs($this->admin)->withSession(['laboratorio_id' => $this->lab]);

    $this->post(route('lab.financeiro.credito', ['fatura' => $id]), ['motivo' => 'Exame repetido por falha nossa', 'valor' => '25,50'])->assertSessionHasNoErrors();
    expect(DB::table('creditos_fatura')->first())->toMatchObject(['valor_centavos' => 2550, 'motivo' => 'Exame repetido por falha nossa']);

    $this->post(route('lab.financeiro.credito', ['fatura' => $id]), ['motivo' => 'Desconto', 'valor' => '90,00'])->assertSessionHasErrors(['form' => 'O crédito não pode passar do total da fatura.']);
    $this->post(route('lab.financeiro.credito', ['fatura' => $id]), ['motivo' => 'Desconto', 'valor' => 'dez'])->assertSessionHasErrors('valor');
    expect(DB::table('creditos_fatura')->count())->toBe(1);
});

it('fechar agora fecha o mês anterior ao de hoje sem esperar o dia da clínica', function (): void {
    $anterior = now('America/Sao_Paulo')->subMonthNoOverflow();
    CenarioDeFatura::pedido($this->lab, $this->c, 1, liberadoEm: $anterior->format('Y-m-15').' 15:00:00');
    $this->actingAs($this->admin)->withSession(['laboratorio_id' => $this->lab]);

    $this->post(route('lab.financeiro.fechar'))->assertSessionHasNoErrors()->assertSessionHas('success', '1 fatura fechada e colocada na fila de envio.');
    expect(DB::table('faturas')->value('competencia'))->toBe($anterior->format('Y-m'));

    $this->post(route('lab.financeiro.fechar'))->assertSessionHas('success', 'Não há pedidos novos para faturar no mês anterior.');
    expect(DB::table('faturas')->count())->toBe(1);
});

it('mostra o painel da competência com os cartões, o pagador e a fatura escolhida', function (): void {
    CenarioDeFatura::pedido($this->lab, $this->c, 1);
    CenarioDeFatura::pedido($this->lab, $this->c, 2, liberadoEm: '2026-11-12 15:00:00', precos: [3000]);
    $this->actingAs($this->admin)->withSession(['laboratorio_id' => $this->lab]);

    $this->get(route('lab.financeiro.index', ['competencia' => '2026-11']))->assertInertia(fn (Assert $p) => $p
        ->component('lab/financeiro/index')->where('competencia', '2026-11')->where('resumo.clinicas', 12000)->where('resumo.clinicasPedidos', 2)
        ->where('pagadores.0.situacao', 'a_fechar')->where('fatura', null));

    fecharNoDia('2026-12-05');
    $id = DB::table('faturas')->value('id');
    $this->get(route('lab.financeiro.index', ['competencia' => '2026-11', 'fatura' => $id]))->assertInertia(fn (Assert $p) => $p
        ->where('resumo.clinicas', 0)->where('pagadores.0.situacao', 'aberta')->where('pagadores.0.valor', 12000)
        ->where('fatura.numero', 'F-00001')->where('fatura.total', 12000)->has('fatura.linhas', 2));
});

it('conta em atraso a fatura vencida de meses anteriores', function (): void {
    CenarioDeFatura::pedido($this->lab, $this->c, 1, liberadoEm: '2026-07-10 15:00:00');
    fecharNoDia('2026-08-05');
    CenarioDeFatura::enviar(Queue::pushed(EnviarFaturaPorEmail::class)->first());
    $this->actingAs($this->admin)->withSession(['laboratorio_id' => $this->lab]);

    $this->get(route('lab.financeiro.index', ['competencia' => '2026-11']))->assertInertia(fn (Assert $p) => $p->where('resumo.emAtraso', 9000)->where('resumo.emAtrasoQuem', 'Clínica Patas'));
});

it('baixa o PDF da fatura', function (): void {
    CenarioDeFatura::pedido($this->lab, $this->c, 1);
    fecharNoDia('2026-12-05');
    $id = DB::table('faturas')->value('id');

    $this->actingAs($this->admin)->withSession(['laboratorio_id' => $this->lab])->get(route('lab.financeiro.pdf', ['fatura' => $id]))
        ->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('Content-Disposition', 'attachment; filename="fatura-F-00001.pdf"');
});

it('só o admin entra no financeiro', function (string $papel): void {
    $usuario = Laboratorios::usuarioEm($this->lab, $papel);
    $this->actingAs($usuario)->withSession(['laboratorio_id' => $this->lab]);

    $this->get(route('lab.financeiro.index'))->assertForbidden();
    $this->post(route('lab.financeiro.fechar'))->assertForbidden();
    $this->post(route('lab.financeiro.pagamento', ['fatura' => (string) Str::uuid7()]))->assertForbidden();
})->with(['bancada', 'recepcao', 'rt']);

it('um laboratório não vê a fatura do outro e o comando roda cada um no seu contexto', function (): void {
    $outro = Laboratorios::criar('Laboratório Beta');
    $adminBeta = Laboratorios::usuarioEm($outro);
    $cBeta = CenarioDePedidos::criar($outro, 'Rex', '19998124411');
    DB::table('clinicas')->where('id', $cBeta['clinica'])->update(['dia_fechamento' => 5, 'dia_vencimento' => 20, 'nome_fantasia' => 'Clínica Beta']);
    CenarioDeFatura::pedido($this->lab, $this->c, 1);
    CenarioDeFatura::pedido($outro, $cBeta, 1, precos: [3000]);

    fecharNoDia('2026-12-05');

    expect(DB::table('faturas')->where('laboratorio_id', $this->lab)->value('pagador_nome'))->toBe('Clínica Patas')
        ->and(DB::table('faturas')->where('laboratorio_id', $outro)->value('pagador_nome'))->toBe('Clínica Beta')
        ->and(DB::table('linhas_fatura')->where('laboratorio_id', $outro)->value('valor_centavos'))->toBe(3000)
        ->and(DB::table('linhas_fatura')->where('laboratorio_id', $this->lab)->value('valor_centavos'))->toBe(9000);

    app(ContextoLaboratorio::class)->executarComo($this->lab, fn () => expect(FaturaModel::query()->pluck('pagador_nome')->all())->toBe(['Clínica Patas']));
    $this->actingAs($adminBeta)->withSession(['laboratorio_id' => $outro])->get(route('lab.financeiro.index', ['competencia' => '2026-11', 'fatura' => DB::table('faturas')->where('laboratorio_id', $this->lab)->value('id')]))
        ->assertInertia(fn (Assert $p) => $p->where('pagadores.0.pagador', 'Clínica Beta')->has('pagadores', 1)->where('fatura', null));
});
