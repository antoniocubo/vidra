<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\CenarioDePainel;
use Tests\Support\Laboratorios;
use Tests\Support\RelogioDePedidosFixo;
use Vidra\Painel\Application\ConsultarPainel;
use Vidra\Painel\Infrastructure\PainelServiceProvider;
use Vidra\Pedidos\Domain\Relogio;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;

beforeEach(function (): void {
    // O registro de produção está pendente de autorização para bootstrap/providers.php.
    app()->register(PainelServiceProvider::class);
    app()->instance(Relogio::class, new RelogioDePedidosFixo(new DateTimeImmutable('2026-11-08 15:00:00 UTC')));
    $this->lab = Laboratorios::criar();
    $this->usuario = Laboratorios::usuarioEm($this->lab);
    $this->usuario->forceFill(['name' => 'Bruno Silva'])->save();
});

it('redireciona o visitante para o login', function (): void {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('conta cada indicador apenas no laboratório atual e isola todos os blocos', function (): void {
    $outro = Laboratorios::criar();
    foreach ([$this->lab, $outro] as $lab) {
        CenarioDePainel::pedido($lab, 1);
        CenarioDePainel::pedido($lab, 2, 'em_analise', prioridade: 'urgente');
        CenarioDePainel::pedido($lab, 3, 'resultado_digitado');
        $liberado = CenarioDePainel::pedido($lab, 4, 'liberado', '2026-11-07 12:00:00');
        CenarioDePainel::laudo($lab, $liberado['pedido'], 1);
    }
    $this->actingAs($this->usuario)->get('/dashboard')->assertOk()->assertInertia(fn (Assert $p) => $p
        ->component('dashboard')->where('primeiroNome', 'Bruno')->where('painel.saudacao', 'Boa tarde')
        ->where('painel.indicadores', ['pedidosHoje' => 3, 'amostrasAguardando' => 1, 'naBancada' => 1, 'aguardandoLiberacao' => 1, 'laudosHoje' => 1])
        ->has('painel.andamento', 3)->has('painel.atencao', 1));
    $vazio = Laboratorios::criar();
    $dados = app(ContextoLaboratorio::class)->executarComo($vazio, fn () => app(ConsultarPainel::class)());
    expect(array_sum($dados['indicadores']))->toBe(0)->and($dados['andamento'])->toBe([])->and($dados['atencao'])->toBe([]);
});

it('calcula atraso pelo prazo de cada exame desde a abertura e exclui itens encerrados', function (): void {
    CenarioDePainel::pedido($this->lab, 1, 'em_analise', '2026-11-08 10:00:00', 2);
    CenarioDePainel::pedido($this->lab, 2, 'em_analise', '2026-11-08 10:00:00', 8);
    CenarioDePainel::pedido($this->lab, 3, 'liberado', '2026-11-08 10:00:00', 2);
    CenarioDePainel::pedido($this->lab, 4, 'cancelado', '2026-11-08 10:00:00', 2);
    CenarioDePainel::pedido($this->lab, 5, 'em_analise', '2026-11-08 13:00:00', 2);
    $this->actingAs($this->usuario)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('painel.atencao', 1)->where('painel.atencao.0.detalhe', 'Exame 1 · Atrasado há 3 h'));
});

it('mostra o crítico até o aviso correspondente e volta a alertar se houver nova digitação', function (): void {
    $c = CenarioDePainel::pedido($this->lab, 1, 'em_analise');
    $id = (string) Str::uuid7();
    DB::table('resultados')->insert(['id' => $id, 'laboratorio_id' => $this->lab, 'pedido_id' => $c['pedido'], 'item_id' => $c['item'], 'paciente_id' => Str::uuid7(), 'analito_id' => Str::uuid7(), 'nome' => 'Potássio', 'valor' => '9', 'digitado_por' => $this->usuario->id, 'digitador_nome' => 'Bruno', 'digitado_em' => '2026-11-08 14:00:00', 'classificacao' => 'critico_alto']);
    $this->actingAs($this->usuario)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('painel.atencao.0.tipo', 'critico'));
    DB::table('avisos_valor_critico')->insert(['id' => Str::uuid7(), 'laboratorio_id' => $this->lab, 'pedido_id' => $c['pedido'], 'resultado_id' => $id, 'valor_avisado' => '9', 'resultado_digitado_em' => '2026-11-08 14:00:00', 'avisado_por' => $this->usuario->id, 'avisado_em' => '2026-11-08 14:10:00', 'destinatario' => 'Solicitante', 'telefone' => '11999999999']);
    $this->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('painel.atencao', 0));
    DB::table('resultados')->where('id', $id)->update(['digitado_em' => '2026-11-08 14:30:00']);
    $this->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('painel.atencao.0.tipo', 'critico'));
});

it('mantém o urgente na atenção até a liberação', function (): void {
    $c = CenarioDePainel::pedido($this->lab, 1, 'resultado_digitado', prioridade: 'urgente');
    $this->actingAs($this->usuario)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('painel.atencao.0.tipo', 'urgente'));
    DB::table('pedido_itens')->where('id', $c['item'])->update(['situacao' => 'liberado']);
    CenarioDePainel::laudo($this->lab, $c['pedido'], 1);
    $this->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('painel.atencao', 0));
});

it('retira a recoleta da atenção quando a nova amostra chega', function (): void {
    $c = CenarioDePainel::pedido($this->lab, 1);
    DB::table('amostras')->where('id', $c['amostra'])->update(['situacao' => 'rejeitada', 'recoleta_codigo' => 'NOVA']);
    $nova = (string) Str::uuid7();
    DB::table('amostras')->insert(['id' => $nova, 'laboratorio_id' => $this->lab, 'pedido_id' => $c['pedido'], 'codigo' => 'NOVA', 'material' => 'Sangue', 'situacao' => 'aguardando']);
    $this->actingAs($this->usuario)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('painel.atencao.0.tipo', 'recoleta'));
    DB::table('amostras')->where('id', $nova)->update(['situacao' => 'recebida', 'recebida_em' => '2026-11-08 14:00:00']);
    $this->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('painel.atencao', 0));
});

it('usa o dia de São Paulo com intervalo fechado na abertura e aberto no fim', function (): void {
    foreach (['2026-11-08 02:59:59', '2026-11-08 03:00:00', '2026-11-09 02:59:59', '2026-11-09 03:00:00'] as $n => $hora) {
        $c = CenarioDePainel::pedido($this->lab, $n + 1, 'liberado', $hora);
        CenarioDePainel::laudo($this->lab, $c['pedido'], $n + 1, $hora);
    }
    $this->actingAs($this->usuario)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('painel.indicadores.pedidosHoje', 2)->where('painel.indicadores.laudosHoje', 2));
});

it('limita atenção a oito e andamento a vinte com uma consulta por bloco', function (): void {
    for ($n = 1; $n <= 25; $n++) {
        CenarioDePainel::pedido($this->lab, $n, prioridade: 'urgente');
    }
    $dados = app(ContextoLaboratorio::class)->executarComo($this->lab, function (): array {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $dados = app(ConsultarPainel::class)();
        expect(DB::getQueryLog())->toHaveCount(3);
        DB::disableQueryLog();

        return $dados;
    });
    expect($dados['atencao'])->toHaveCount(8)->and($dados['andamento'])->toHaveCount(20)->and($dados['indicadores']['pedidosHoje'])->toBe(25);
});
