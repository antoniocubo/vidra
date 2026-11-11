<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\CenarioDeEntrega;
use Tests\Support\Laboratorios;

beforeEach(function (): void {
    $this->c = CenarioDeEntrega::liberar($this);
});

it('lista somente laudos que já foram liberados', function (): void {
    $this->post(route('lab.pedidos.store'), [
        'paciente_id' => DB::table('pedidos')->where('id', $this->c['pedido'])->value('paciente_id'),
        'solicitante_id' => $this->c['vinculo'], 'pagador' => 'responsavel', 'prioridade' => 'rotina',
        'exame_ids' => [DB::table('exames')->where('laboratorio_id', $this->c['lab'])->value('id')],
    ])->assertSessionHasNoErrors();
    $this->get(route('lab.laudos.index'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('lab/laudos/index')
        ->has('laudos', 1)->where('laudos.0.numero', 'L-00001')->where('laudos.0.paciente', 'Thor')->where('laudos.0.emailOk', false));
});

it('filtra pela liberação em São Paulo mesmo com pedido aberto ontem', function (): void {
    DB::table('pedidos')->where('id', $this->c['pedido'])->update(['criado_em' => '2026-11-09 12:00:00']);
    DB::table('laudos')->where('id', $this->c['laudo'])->update(['liberado_em' => '2026-11-11 02:59:59']);
    $this->get(route('lab.laudos.index', ['de' => '2026-11-10', 'ate' => '2026-11-10']))->assertInertia(fn (Assert $p) => $p->has('laudos', 1)->where('laudos.0.liberadoEm', '10/11/2026 23:59'));
    DB::table('laudos')->where('id', $this->c['laudo'])->update(['liberado_em' => '2026-11-11 03:00:00']);
    $this->get(route('lab.laudos.index', ['de' => '2026-11-10', 'ate' => '2026-11-10']))->assertInertia(fn (Assert $p) => $p->has('laudos', 0));
});

it('busca por protocolo número paciente e solicitante', function (string $busca, int $quantidade): void {
    $this->get(route('lab.laudos.index', ['busca' => $busca]))->assertInertia(fn (Assert $p) => $p->has('laudos', $quantidade));
})->with([['P-00001', 1], ['L-00001', 1], ['Thor', 1], ['Rafael', 1], ['Inexistente', 0]]);

it('filtra falhas de e-mail e mantém o resumo dos acessos sem duplicar laudos', function (): void {
    foreach (['falhou', 'enviado'] as $situacao) {
        DB::table('entregas_laudo')->insert(['id' => (string) Str::uuid7(), 'laboratorio_id' => $this->c['lab'], 'laudo_id' => $this->c['laudo'], 'envio_id' => (string) Str::uuid7(), 'canal' => 'email', 'destinatario' => 'tutor@exemplo.com', 'papel' => 'tutor', 'situacao' => $situacao, 'quando' => now()]);
    }
    foreach ([1, 2] as $i) {
        DB::table('acessos_laudo')->insert(['id' => (string) Str::uuid7(), 'laboratorio_id' => $this->c['lab'], 'laudo_id' => $this->c['laudo'], 'quando' => '2026-11-10 15:00:00', 'ip' => '127.0.0.1', 'user_agent' => 'Teste '.$i]);
    }
    $this->get(route('lab.laudos.index', ['entrega' => 'falhou']))->assertInertia(fn (Assert $p) => $p->has('laudos', 1)->where('laudos.0.emailFalhou', true)->where('laudos.0.emailOk', true)->where('laudos.0.acessos', 2)->where('laudos.0.ultimoAcesso', '10/11/2026 12:00'));
});

it('não aberto exige email entregue e nenhum acesso ao link', function (): void {
    $this->get(route('lab.laudos.index', ['entrega' => 'nao_aberto']))->assertInertia(fn (Assert $p) => $p->has('laudos', 0));
    DB::table('entregas_laudo')->insert(['id' => (string) Str::uuid7(), 'laboratorio_id' => $this->c['lab'], 'laudo_id' => $this->c['laudo'], 'envio_id' => (string) Str::uuid7(), 'canal' => 'email', 'destinatario' => 'tutor@exemplo.com', 'papel' => 'tutor', 'situacao' => 'enviado', 'quando' => now()]);
    $this->get(route('lab.laudos.index', ['entrega' => 'nao_aberto']))->assertInertia(fn (Assert $p) => $p->has('laudos', 1));
    DB::table('acessos_laudo')->insert(['id' => (string) Str::uuid7(), 'laboratorio_id' => $this->c['lab'], 'laudo_id' => $this->c['laudo'], 'quando' => now(), 'ip' => '127.0.0.1', 'user_agent' => 'Teste']);
    $this->get(route('lab.laudos.index', ['entrega' => 'nao_aberto']))->assertInertia(fn (Assert $p) => $p->has('laudos', 0));
});

it('mostra versão dois e retificação em andamento', function (): void {
    DB::table('laudos')->where('id', $this->c['laudo'])->update(['versao' => 2, 'reaberto_em' => now(), 'reaberto_motivo' => 'Conferir']);
    $this->get(route('lab.laudos.index'))->assertInertia(fn (Assert $p) => $p->where('laudos.0.numero', 'L-00001 v2')->where('laudos.0.retificado', true)->where('laudos.0.emRetificacao', true));
});

it('a bancada vê a lista e recebe somente as áreas que permitem suas ações', function (): void {
    $usuario = Laboratorios::usuarioEm($this->c['lab'], 'bancada');
    $this->actingAs($usuario)->get(route('lab.laudos.index'))->assertOk()->assertInertia(fn (Assert $p) => $p->has('laudos', 1)
        ->where('laboratorio.areas', fn ($areas) => in_array('laudos', $areas->all(), true) && ! in_array('pedidos', $areas->all(), true)));
    // A tela condiciona as duas ações à mesma área compartilhada pelo painel.
    $tela = file_get_contents(resource_path('js/pages/lab/laudos/index.tsx'));
    expect($tela)->toContain("areas.includes('pedidos')", 'podeAbrirPedidos &&');
});

it('não mostra laudos de outro laboratório', function (): void {
    $outro = Laboratorios::criar();
    $this->actingAs(Laboratorios::usuarioEm($outro))->withSession(['laboratorio_id' => $outro])
        ->get(route('lab.laudos.index'))->assertInertia(fn (Assert $p) => $p->has('laudos', 0));
});

it('o cartão do painel aponta para laudos pela data da liberação', function (): void {
    $this->get(route('dashboard'))->assertOk();
    expect(file_get_contents(resource_path('js/pages/dashboard.tsx')))->toContain("area: 'laudos',\n            href: route('lab.laudos.index', { de: painel.data, ate: painel.data })");
});

it('rejeita filtros inválidos', function (array $filtros, string $campo): void {
    $this->get(route('lab.laudos.index', $filtros))->assertSessionHasErrors($campo);
})->with([[['de' => '2026-02-30'], 'de'], [['de' => '2026-11-10', 'ate' => '2026-11-09'], 'ate'], [['entrega' => 'qualquer'], 'entrega'], [['pagina' => 0], 'pagina']]);

it('permite filtrar somente pela data final', function (): void {
    $this->get(route('lab.laudos.index', ['ate' => '2099-01-01']))->assertSessionHasNoErrors()->assertInertia(fn (Assert $p) => $p->has('laudos', 1));
});
