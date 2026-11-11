<?php

declare(strict_types=1);

use App\Models\ContaVeterinario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\CenarioDeEntrega;
use Tests\Support\Laboratorios;
use Tests\Support\Veterinarios;
use Vidra\Laudos\Infrastructure\Jobs\EntregarLaudo;

it('vê só os pedidos do próprio vínculo e recusa PDF alheio com 404', function (): void {
    $lab = Laboratorios::criar();
    $vet = Veterinarios::comContaEm([$lab]);
    $colega = Veterinarios::comContaEm([$lab]);
    $proprio = Veterinarios::pedido($lab, Veterinarios::vinculoDa($vet, $lab));
    $outro = Veterinarios::pedido($lab, Veterinarios::vinculoDa($colega, $lab));
    $this->actingAs($vet, 'vet')->get(route('vet.inicio'))->assertInertia(fn (Assert $p) => $p->has('lista.pedidos', 1)->where('lista.pedidos.0.id', $proprio));
    $this->get(route('vet.laudo.pdf', $outro))->assertNotFound();
    $this->get(route('vet.laudo.pdf', $proprio))->assertNotFound();
});

it('vê colegas da mesma clínica quando autorizado e exclui colegas de outra clínica', function (): void {
    $lab = Laboratorios::criar();
    $vet = Veterinarios::comContaEm([$lab], true);
    $colega = Veterinarios::comContaEm([$lab]);
    $outro = Veterinarios::comContaEm([$lab]);
    $proprio = Veterinarios::pedido($lab, Veterinarios::vinculoDa($vet, $lab));
    $permitido = Veterinarios::pedido($lab, Veterinarios::vinculoDa($colega, $lab));
    Veterinarios::pedido($lab, Veterinarios::vinculoDa($outro, $lab));
    $clinica = DB::table('clinicas')->where('laboratorio_id', $lab)->value('id');
    foreach ([$vet, $colega] as $conta) {
        DB::table('clinica_veterinario')->insert(['laboratorio_id' => $lab, 'clinica_id' => $clinica, 'vinculo_id' => Veterinarios::vinculoDa($conta, $lab), 'created_at' => now(), 'updated_at' => now()]);
    }
    $outraClinica = (array) DB::table('clinicas')->where('id', $clinica)->first();
    $outraClinica['id'] = (string) Illuminate\Support\Str::uuid7();
    $outraClinica['nome_fantasia'] = 'Outra clínica';
    DB::table('clinicas')->insert($outraClinica);
    DB::table('clinica_veterinario')->insert(['laboratorio_id' => $lab, 'clinica_id' => $outraClinica['id'], 'vinculo_id' => Veterinarios::vinculoDa($outro, $lab), 'created_at' => now(), 'updated_at' => now()]);
    $this->actingAs($vet, 'vet')->get(route('vet.inicio'))->assertInertia(fn (Assert $p) => $p->has('lista.pedidos', 2)->where('lista.pedidos', fn ($pedidos) => collect($pedidos)->pluck('id')->sort()->values()->all() === collect([$proprio, $permitido])->sort()->values()->all()));
    DB::table('laboratorio_veterinario')->where('id', Veterinarios::vinculoDa($vet, $lab))->update(['ve_pedidos_dos_colegas' => false]);
    $this->get(route('vet.inicio'))->assertInertia(fn (Assert $p) => $p->has('lista.pedidos', 1));
});

it('não mistura pedidos de outro laboratório mesmo tendo vínculo nos dois', function (): void {
    $a = Laboratorios::criar('Alfa');
    $b = Laboratorios::criar('Beta');
    $vet = Veterinarios::comContaEm([$a, $b], true);
    $proprio = Veterinarios::pedido($a, Veterinarios::vinculoDa($vet, $a));
    $outro = Veterinarios::pedido($b, Veterinarios::vinculoDa($vet, $b));
    $this->actingAs($vet, 'vet')->withSession(['vet_laboratorio_id' => $a])->get(route('vet.inicio'))->assertInertia(fn (Assert $p) => $p->has('lista.pedidos', 1)->where('lista.pedidos.0.id', $proprio)->where('lista.resumo.mes', 1));
    $this->get(route('vet.laudo.pdf', $outro))->assertNotFound();
});

it('pagina vinte pedidos sem truncar o histórico e busca por animal ou tutor', function (): void {
    $lab = Laboratorios::criar();
    $vet = Veterinarios::comContaEm([$lab]);
    foreach (range(1, 21) as $_) {
        Veterinarios::pedido($lab, Veterinarios::vinculoDa($vet, $lab));
    }
    $this->actingAs($vet, 'vet')->get(route('vet.inicio'))->assertInertia(fn (Assert $p) => $p->has('lista.pedidos', 20)->where('lista.total', 21)->where('lista.paginas', 2));
    $this->get(route('vet.inicio', ['pagina' => 2, 'busca' => 'carla']))->assertInertia(fn (Assert $p) => $p->has('lista.pedidos', 1)->where('lista.total', 21));
    $this->get(route('vet.inicio', ['busca' => 'thor']))->assertInertia(fn (Assert $p) => $p->where('lista.total', 21));
    $this->get(route('vet.inicio', ['busca' => 'inexistente']))->assertInertia(fn (Assert $p) => $p->has('lista.pedidos', 0)->where('lista.resumo.mes', 21));
});

it('baixa só laudo liberado, registra acesso mínimo e marca o laudo com valor crítico avisado', function (): void {
    Http::fake(fn () => Http::response('%PDF-1.7 vet', 200, ['Content-Type' => 'application/pdf']));
    Storage::fake('laudos');
    $c = CenarioDeEntrega::liberar($this);
    CenarioDeEntrega::executar(Queue::pushed(EntregarLaudo::class)->first());
    $pessoa = DB::table('laboratorio_veterinario')->where('id', $c['vinculo'])->value('veterinario_id');
    $email = DB::table('veterinarios')->where('id', $pessoa)->value('email');
    $vet = ContaVeterinario::query()->create(['veterinario_id' => $pessoa, 'email' => $email, 'password' => Veterinarios::SENHA]);
    // Crítico de verdade sempre tem aviso registrado antes da liberação.
    $resultado = DB::table('resultados')->where('pedido_id', $c['pedido'])->value('id');
    DB::table('avisos_valor_critico')->insert(['id' => (string) Illuminate\Support\Str::uuid7(), 'laboratorio_id' => $c['lab'], 'pedido_id' => $c['pedido'], 'resultado_id' => $resultado, 'valor_avisado' => '9',
        'resultado_digitado_em' => '2026-11-08 14:00:00', 'avisado_por' => 1, 'avisado_em' => '2026-11-08 14:10:00', 'destinatario' => 'Solicitante', 'telefone' => '11999999999']);
    $this->actingAs($vet, 'vet')->get(route('vet.inicio'))->assertInertia(fn (Assert $p) => $p->where('lista.pedidos.0.critico', true)->where('lista.pedidos.0.pdfPronto', true)->where('lista.pedidos.0.whatsapp', fn ($url) => str_contains($url, 'wa.me/') && str_contains(urldecode($url), str_replace('-', '', $c['codigo']))));
    $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.55'])->withHeader('User-Agent', str_repeat('v', 250))->get(route('vet.laudo.pdf', $c['pedido']))->assertOk()->assertContent('%PDF-1.7 vet');
    $this->assertDatabaseHas('acessos_laudo', ['laudo_id' => $c['laudo'], 'laboratorio_id' => $c['lab'], 'ip' => '192.168.1.0', 'user_agent' => str_repeat('v', 200)]);
});

it('entrega a versão atual do PDF e conta laudos pela data de liberação', function (): void {
    Http::fakeSequence()->push('%PDF-1.7 primeira', 200, ['Content-Type' => 'application/pdf'])->push('%PDF-1.7 atual', 200, ['Content-Type' => 'application/pdf']);
    Storage::fake('laudos');
    $c = CenarioDeEntrega::liberar($this);
    CenarioDeEntrega::executar(Queue::pushed(EntregarLaudo::class)->first());
    $this->post(route('lab.pedidos.laudo.retificar', $c['pedido']), ['motivo' => 'Correção conferida pelo RT'])->assertSessionHasNoErrors();
    $this->post(route('lab.liberacao.liberar', $c['pedido']))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('laudos', ['id' => $c['laudo'], 'versao' => 2]);
    CenarioDeEntrega::executar(Queue::pushed(EntregarLaudo::class)->last());
    $pessoa = DB::table('laboratorio_veterinario')->where('id', $c['vinculo'])->value('veterinario_id');
    $vet = ContaVeterinario::query()->create(['veterinario_id' => $pessoa, 'email' => DB::table('veterinarios')->where('id', $pessoa)->value('email'), 'password' => Veterinarios::SENHA]);
    DB::table('pedidos')->where('id', $c['pedido'])->update(['criado_em' => now()->subMonths(2)]);
    $this->actingAs($vet, 'vet')->get(route('vet.inicio'))->assertInertia(fn (Assert $p) => $p->where('lista.resumo.mes', 0)->where('lista.resumo.prontosHoje', 1));
    $this->get(route('vet.laudo.pdf', $c['pedido']))->assertOk()->assertContent('%PDF-1.7 atual');
    $outro = Veterinarios::comContaEm([$c['lab']]);
    $this->actingAs($outro, 'vet')->get(route('vet.laudo.pdf', $c['pedido']))->assertNotFound();
});

it('a escolha conta novidades só do vínculo e guarda o último acesso depois de sair', function (): void {
    $c = CenarioDeEntrega::liberar($this);
    $pessoa = DB::table('laboratorio_veterinario')->where('id', $c['vinculo'])->value('veterinario_id');
    $vet = ContaVeterinario::query()->create(['veterinario_id' => $pessoa, 'email' => DB::table('veterinarios')->where('id', $pessoa)->value('email'), 'password' => Veterinarios::SENHA]);
    $this->actingAs($vet, 'vet')->get(route('vet.laboratorio.escolher'))->assertInertia(fn (Assert $p) => $p->where('novidades.'.$c['lab'].'.novos', 1));
    $this->get(route('vet.inicio'))->assertOk();
    $this->post(route('vet.sair'));
    $this->post(route('vet.entrar.store'), ['email' => $vet->email, 'senha' => Veterinarios::SENHA]);
    $this->get(route('vet.laboratorio.escolher'))->assertInertia(fn (Assert $p) => $p->where('novidades.'.$c['lab'].'.novos', 0));
});
