<?php

declare(strict_types=1);

use App\Models\ContaVeterinario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\CenarioDePedidos;
use Tests\Support\Laboratorios;
use Vidra\Veterinaria\Infrastructure\Mail\AcessoDoVeterinarioMail;

beforeEach(function (): void {
    Mail::fake();
    $this->lab = Laboratorios::criar();
    $this->c = CenarioDePedidos::criar($this->lab);
    $this->usuario = Laboratorios::usuarioEm($this->lab, 'recepcao');
    $this->actingAs($this->usuario)->withSession(['laboratorio_id' => $this->lab]);
    $this->url = route('lab.veterinarios.acesso', $this->c['vinculo']);
});

it('recepção envia convite por email com hash e sete dias de validade e substitui o pendente', function (): void {
    $this->post($this->url)->assertSessionHasNoErrors();
    Mail::assertSent(AcessoDoVeterinarioMail::class, 1);
    $mail = Mail::sent(AcessoDoVeterinarioMail::class)->first();
    expect(strlen($mail->token))->toBe(48);
    $r = DB::table('convites_veterinario')->first();
    expect($r->token_hash)->toBe(hash('sha256', $mail->token))->not->toBe($mail->token);
    expect(now()->diffInDays(Carbon\Carbon::parse($r->expira_em)))->toBeGreaterThan(6.9)->toBeLessThan(7.1);
    expect($mail->render())->toContain($this->lab ? 'liberou para você' : '', route('vet.convite.show', $mail->token));
    $this->post($this->url)->assertSessionHasNoErrors();
    expect(DB::table('convites_veterinario')->whereNull('cancelado_em')->count())->toBe(1);
    $this->assertDatabaseHas('convites_veterinario', ['id' => $r->id, 'cancelado_em' => now()->format('Y-m-d H:i:s')]);
});

it('aceita o convite cria conta com senha segura e entra pelo guard vet sem trocar a equipe', function (): void {
    $this->post($this->url);
    $token = Mail::sent(AcessoDoVeterinarioMail::class)->first()->token;
    $this->get(route('vet.convite.show', $token))->assertInertia(fn (Assert $p) => $p->component('vet/convite')->where('erro', null));
    $this->post(route('vet.convite.store', $token), ['senha' => 'senha-do-vet-nova', 'senha_confirmation' => 'senha-do-vet-nova'])->assertRedirect(route('vet.inicio'))->assertSessionHas('vet_laboratorio_id', $this->lab);
    $conta = ContaVeterinario::query()->firstOrFail();
    expect(Hash::check('senha-do-vet-nova', $conta->getAuthPassword()))->toBeTrue();
    $this->assertAuthenticatedAs($conta, 'vet');
    $this->assertAuthenticatedAs($this->usuario, 'web');
    expect(DB::table('convites_veterinario')->whereNotNull('aceito_em')->count())->toBe(1);
    $this->post(route('vet.convite.store', $token), ['senha' => 'outra-senha-123', 'senha_confirmation' => 'outra-senha-123'])->assertSessionHasErrors('form');
    expect(ContaVeterinario::query()->count())->toBe(1);
});

it('recusa token vencido e inválido sem criar conta', function (): void {
    $this->post($this->url);
    $token = Mail::sent(AcessoDoVeterinarioMail::class)->first()->token;
    DB::table('convites_veterinario')->update(['expira_em' => now()->subSecond()]);
    $this->post(route('vet.convite.store', $token), ['senha' => 'senha-veterinario', 'senha_confirmation' => 'senha-veterinario'])->assertSessionHasErrors('form');
    $this->post(route('vet.convite.store', 'invalido'), ['senha' => 'senha-veterinario', 'senha_confirmation' => 'senha-veterinario'])->assertSessionHasErrors('form');
    $this->assertDatabaseCount('contas_veterinario', 0);
});

it('recusa conta existente e a edição mostra o email que já acessa', function (): void {
    $this->post($this->url);
    $token = Mail::sent(AcessoDoVeterinarioMail::class)->first()->token;
    $id = DB::table('laboratorio_veterinario')->where('id', $this->c['vinculo'])->value('veterinario_id');
    $email = DB::table('veterinarios')->where('id', $id)->value('email');
    ContaVeterinario::query()->create(['veterinario_id' => $id, 'email' => $email, 'password' => 'senha-veterinario']);
    $this->post(route('vet.convite.store', $token), ['senha' => 'senha-veterinario', 'senha_confirmation' => 'senha-veterinario'])->assertSessionHasErrors(['form' => 'Você já tem acesso. Entre com sua senha.']);
    $this->post($this->url)->assertSessionHasErrors('acesso');
    $this->get(route('lab.veterinarios.edit', $this->c['vinculo']))->assertInertia(fn (Assert $p) => $p->where('emailDeAcesso', $email));
});

it('bancada não envia acesso e vínculo alheio não é encontrado', function (): void {
    $bancada = Laboratorios::usuarioEm($this->lab, 'bancada');
    $this->actingAs($bancada)->post($this->url)->assertForbidden();
    Mail::assertNothingSent();
    $outro = CenarioDePedidos::criar(Laboratorios::criar('Outro'));
    $this->actingAs($this->usuario)->post(route('lab.veterinarios.acesso', $outro['vinculo']))->assertNotFound();
});

it('recusa senha curta e vínculo removido depois do envio', function (): void {
    $this->post($this->url);
    $token = Mail::sent(AcessoDoVeterinarioMail::class)->first()->token;
    $this->post(route('vet.convite.store', $token), ['senha' => 'curta', 'senha_confirmation' => 'curta'])->assertSessionHasErrors('senha');
    DB::table('laboratorio_veterinario')->where('id', $this->c['vinculo'])->delete();
    $this->post(route('vet.convite.store', $token), ['senha' => 'senha-veterinario', 'senha_confirmation' => 'senha-veterinario'])->assertSessionHasErrors('form');
    $this->assertDatabaseCount('contas_veterinario', 0);
});

it('o novo envio invalida o token anterior e mantém o novo utilizável', function (): void {
    $this->post($this->url);
    $anterior = Mail::sent(AcessoDoVeterinarioMail::class)->first()->token;
    $this->post($this->url);
    $novo = Mail::sent(AcessoDoVeterinarioMail::class)->last()->token;
    $this->post(route('vet.convite.store', $anterior), ['senha' => 'senha-veterinario', 'senha_confirmation' => 'senha-veterinario'])->assertSessionHasErrors('form');
    $this->post(route('vet.convite.store', $novo), ['senha' => 'senha-veterinario', 'senha_confirmation' => 'senha-veterinario'])->assertRedirect(route('vet.inicio'));
});

it('limita o envio de acesso a cinco tentativas por minuto', function (): void {
    foreach (range(1, 5) as $_) {
        $this->post($this->url)->assertSessionHasNoErrors();
    }
    $this->post($this->url)->assertStatus(429);
    Mail::assertSent(AcessoDoVeterinarioMail::class, 5);
});
