<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\Laboratorios;
use Vidra\Acesso\Infrastructure\Mail\ConviteMail;

beforeEach(function (): void {
    Mail::fake();
    $this->lab = Laboratorios::criar();
    $this->admin = Laboratorios::usuarioEm($this->lab);
    $this->actingAs($this->admin)->post('/equipe/convites', ['nome' => 'Ana Souza', 'email' => 'ana@example.com', 'papel' => 'rt'])->assertRedirect();
    $mail = Mail::sent(ConviteMail::class)->first();
    $this->token = basename($mail->link);
    Auth::logout();
    $this->dados = ['nome' => 'Ana Souza', 'password' => 'senha-segura-123', 'password_confirmation' => 'senha-segura-123'];
});

it('mostra o convite público sem exigir acesso ao laboratório', function (): void {
    $this->get('/convite/'.$this->token)->assertOk()->assertSessionHas('url.intended', url('/convite/'.$this->token))
        ->assertInertia(fn (Assert $page) => $page->component('acesso/convite')->where('convite.email', 'ana@example.com')->where('convite.temConta', false)->where('convite.laboratorio', 'Laboratório Alfa')->where('erro', null));
});

it('cria conta verificada aceita o convite faz login e escolhe o laboratório', function (): void {
    $this->post('/convite/'.$this->token, $this->dados)->assertRedirect('/dashboard')->assertSessionHas('laboratorio_id', $this->lab)->assertSessionMissing('url.intended');
    $ana = User::where('email', 'ana@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($ana);
    expect($ana->email_verified_at)->not->toBeNull()->and(Hash::check('senha-segura-123', $ana->password))->toBeTrue();
    $this->assertDatabaseHas('laboratorio_usuario', ['laboratorio_id' => $this->lab, 'user_id' => $ana->id, 'papel' => 'rt', 'e_rt' => true]);
    expect(DB::table('convites')->value('aceito_em'))->not->toBeNull();
    $this->get('/dashboard')->assertOk();
});

it('aceita com a conta existente autenticada sem criar outro usuário', function (): void {
    $ana = User::factory()->create(['email' => 'ANA@example.com']);
    $beta = Laboratorios::criar('Beta');
    Laboratorios::usuarioEm($beta, 'recepcao', $ana);
    $this->actingAs($ana)->withSession(['laboratorio_id' => $beta])->post('/convite/'.$this->token)->assertRedirect('/dashboard')->assertSessionHas('laboratorio_id', $this->lab);
    $this->assertDatabaseCount('users', 2);
    $this->assertDatabaseHas('laboratorio_usuario', ['laboratorio_id' => $this->lab, 'user_id' => $ana->id, 'e_rt' => true]);
    $this->assertDatabaseHas('laboratorio_usuario', ['laboratorio_id' => $beta, 'user_id' => $ana->id]);
});

it('pede login para conta existente e impede aceite sem autenticação', function (): void {
    User::factory()->create(['email' => 'ana@example.com']);
    $this->get('/convite/'.$this->token)->assertInertia(fn (Assert $page) => $page->where('convite.temConta', true));
    $this->post('/convite/'.$this->token, $this->dados)->assertSessionHasErrors('form');
    $this->assertGuest();
    expect(DB::table('convites')->value('aceito_em'))->toBeNull();
});

it('não aceita o convite autenticado com outro e-mail', function (): void {
    $this->actingAs($this->admin)->post('/convite/'.$this->token)->assertSessionHasErrors('form');
    expect(DB::table('convites')->value('aceito_em'))->toBeNull();
    $this->assertDatabaseCount('laboratorio_usuario', 1);
});

it('mostra erro amigável para token expirado cancelado usado ou desconhecido', function (string $estado): void {
    if ($estado === 'expirado') {
        $this->travel(7)->days();
    }
    if ($estado === 'cancelado') {
        DB::table('convites')->update(['cancelado_em' => now()]);
    }
    if ($estado === 'usado') {
        DB::table('convites')->update(['aceito_em' => now()]);
    }
    if ($estado === 'desconhecido') {
        $this->token = str_repeat('x', 48);
    }
    $this->get('/convite/'.$this->token)->assertOk()->assertInertia(fn (Assert $page) => $page->component('acesso/convite')->where('convite', null)->where('erro', 'Este convite expirou, foi cancelado ou já foi aceito. Peça um novo convite ao administrador.'));
    $this->post('/convite/'.$this->token, $this->dados)->assertInertia(fn (Assert $page) => $page->where('convite', null));
    $this->assertDatabaseCount('users', 1);
    $this->assertGuest();
})->with(['expirado', 'cancelado', 'usado', 'desconhecido']);

it('impede reutilizar o link depois de um aceite real', function (): void {
    $this->post('/convite/'.$this->token, $this->dados)->assertRedirect();
    $this->post('/convite/'.$this->token)->assertInertia(fn (Assert $page) => $page->where('convite', null));
    $this->assertDatabaseCount('users', 2);
    $this->assertDatabaseCount('laboratorio_usuario', 2);
});

it('valida senha e confirmação antes de criar a conta', function (): void {
    $this->post('/convite/'.$this->token, [...$this->dados, 'password_confirmation' => 'outra-senha'])->assertSessionHasErrors('password');
    $this->post('/convite/'.$this->token, [...$this->dados, 'password' => 'curta', 'password_confirmation' => 'curta'])->assertSessionHasErrors('password');
    $this->assertDatabaseCount('users', 1);
    expect(DB::table('convites')->value('aceito_em'))->toBeNull();
});

it('grava e_rt falso para papéis diferentes de rt', function (string $papel): void {
    DB::table('convites')->update(['papel' => $papel]);
    $this->post('/convite/'.$this->token, $this->dados)->assertRedirect();
    $ana = User::where('email', 'ana@example.com')->firstOrFail();
    $this->assertDatabaseHas('laboratorio_usuario', ['laboratorio_id' => $this->lab, 'user_id' => $ana->id, 'papel' => $papel, 'e_rt' => false]);
})->with(['admin', 'recepcao', 'bancada']);

it('limita acessos às rotas públicas de convite', function (): void {
    for ($i = 0; $i < 10; $i++) {
        $this->get('/convite/'.$this->token)->assertOk();
    }
    $this->get('/convite/'.$this->token)->assertStatus(429);
});

it('retorna ao convite depois de entrar pela tela de login', function (): void {
    $ana = User::factory()->create(['email' => 'ana@example.com']);
    $this->get('/convite/'.$this->token)->assertOk();
    $this->post('/login', ['email' => $ana->email, 'password' => 'password'])->assertRedirect('/convite/'.$this->token);
    $this->get('/convite/'.$this->token)->assertOk();
    $this->post('/convite/'.$this->token)->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($ana);
});

it('desfaz a criação da conta quando a inclusão na equipe falha', function (): void {
    $equipe = Mockery::mock(Vidra\Acesso\Domain\Equipe::class);
    $equipe->shouldReceive('contemEmail')->once()->andReturnFalse();
    $equipe->shouldReceive('adicionar')->once()->andThrow(new RuntimeException('Falha na persistência'));
    $this->app->instance(Vidra\Acesso\Domain\Equipe::class, $equipe);
    $this->post('/convite/'.$this->token, $this->dados)->assertServerError();
    $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
    $this->assertDatabaseCount('laboratorio_usuario', 1);
    expect(DB::table('convites')->value('aceito_em'))->toBeNull();
});
