<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\DadosCadastro;

it('mostra as quatro etapas com planos e estados disponíveis', function (): void {
    $this->get(route('cadastro'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('acesso/cadastro')->has('ufs', 27)->has('planos', 3)->where('planos.1.preco', 59000));
});
it('cadastra laboratório usuário e vínculo e entra como administrador', function (): void {
    $this->travelTo(now()->setDate(2026, 11, 6)->setTime(15, 0));
    $this->post(route('cadastro.store'), DadosCadastro::validos())->assertRedirect(route('dashboard'))->assertSessionHas('success', 'Laboratório Alfa Vet cadastrado. Seu teste vai até 20/11/2026.');
    $this->assertAuthenticated();
    $this->assertDatabaseHas('laboratorios', ['nome' => 'Laboratório Alfa Vet', 'cnpj' => '11222333000181', 'telefone' => '1932427180', 'cep' => '13015001', 'linha' => 'pet', 'plano' => 'laboratorio', 'cobranca' => 'mensal', 'teste_termina_em' => '2026-11-20 15:00:00', 'rt_conselho' => 'CRMV', 'rt_email' => null]);
    $user = User::query()->sole();
    $this->assertAuthenticatedAs($user);
    expect(Hash::check('uma-frase-segura-2026', $user->password))->toBeTrue();
    $this->assertDatabaseHas('users', ['email' => 'marina@alfavet.com.br', 'celular' => '19991274410']);
    $this->assertDatabaseHas('laboratorio_usuario', ['laboratorio_id' => DB::table('laboratorios')->value('id'), 'user_id' => $user->id, 'papel' => 'admin', 'e_rt' => true]);
});
it('recusa CNPJ repetido sem deixar cadastro parcial', function (): void {
    $this->post(route('cadastro.store'), DadosCadastro::validos());
    auth()->logout();
    $dados = [...DadosCadastro::validos(), 'cnpj' => '11222333000181', 'email' => 'outro@lab.com.br'];
    $this->post(route('cadastro.store'), $dados)->assertSessionHasErrors('cnpj');
    $this->assertDatabaseCount('laboratorios', 1);
    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('laboratorio_usuario', 1);
    $this->assertGuest();
});
it('recusa e-mail repetido mesmo com maiúsculas', function (): void {
    User::factory()->create(['email' => 'marina@alfavet.com.br']);
    $this->post(route('cadastro.store'), [...DadosCadastro::validos(), 'email' => 'MARINA@alfavet.com.br'])->assertSessionHasErrors('email');
    $this->assertDatabaseCount('laboratorios', 0);
    $this->assertDatabaseCount('users', 1);
    $this->assertGuest();
});
it('recusa a linha clínica humana', function (): void {
    $this->post(route('cadastro.store'), [...DadosCadastro::validos(), 'linha' => 'clinica'])->assertSessionHasErrors('linha');
    $this->assertDatabaseCount('laboratorios', 0);
    $this->assertGuest();
});
it('exige e-mail quando o RT é outra pessoa', function (): void {
    $this->post(route('cadastro.store'), [...DadosCadastro::validos(), 'sou_rt' => false])->assertSessionHasErrors('rt_email');
    $this->assertDatabaseCount('users', 0);
});
it('guarda outro RT sem criar uma segunda conta e aceita cobrança anual', function (): void {
    $this->post(route('cadastro.store'), [...DadosCadastro::validos(), 'sou_rt' => false, 'rt_email' => 'rt@lab.com.br', 'plano' => 'rede', 'cobranca' => 'anual'])->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('laboratorios', ['rt_email' => 'rt@lab.com.br', 'plano' => 'rede', 'cobranca' => 'anual']);
    $this->assertDatabaseHas('laboratorio_usuario', ['papel' => 'admin', 'e_rt' => false]);
    $this->assertDatabaseCount('users', 1);
});
it('devolve erro no campo correto sem gravar dados inválidos', function (string $campo, mixed $valor): void {
    $this->post(route('cadastro.store'), [...DadosCadastro::validos(), $campo => $valor])->assertSessionHasErrors($campo);
    $this->assertDatabaseCount('laboratorios', 0);
    $this->assertDatabaseCount('users', 0);
    $this->assertGuest();
})->with([['cnpj', '11.222.333/0001-80'], ['celular', '123'], ['telefone', '123'], ['cep', '123'], ['uf', 'XX'], ['rt_uf', 'XX'], ['rt_numero', '!'], ['senha', 'curta'], ['termos', false], ['plano', 'inexistente'], ['cobranca', 'gratis']]);
it('não devolve a senha na sessão quando a validação falha', function (): void {
    $this->post(route('cadastro.store'), [...DadosCadastro::validos(), 'termos' => false])->assertSessionHasErrors('termos')->assertSessionMissing('_old_input.senha');
});
it('desfaz o laboratório se a gravação do usuário falhar', function (): void {
    User::creating(static function (): never {
        throw new RuntimeException('Falha simulada');
    });
    try {
        $this->withoutExceptionHandling();
        expect(fn () => $this->post(route('cadastro.store'), DadosCadastro::validos()))->toThrow(RuntimeException::class, 'Falha simulada');
        $this->assertDatabaseCount('laboratorios', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('laboratorio_usuario', 0);
    } finally {
        User::flushEventListeners();
    }
});
it('substitui as rotas antigas do starter kit', function (): void {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});
it('impede novo cadastro para quem já está autenticado', function (): void {
    $this->actingAs(User::factory()->create())->get(route('cadastro'))->assertRedirect();
    $this->post(route('cadastro.store'), DadosCadastro::validos())->assertRedirect();
    $this->assertDatabaseCount('laboratorios', 0);
});

it('recebe o plano e a cobrança escolhidos na landing', function (): void {
    $this->get(route('cadastro', ['plano' => 'rede', 'cobranca' => 'anual']))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('acesso/cadastro')
        ->where('planoInicial', 'rede')
        ->where('cobrancaInicial', 'anual'));
});

it('ignora plano e cobrança inválidos ou ausentes sem erro', function (array $parametros): void {
    $this->get(route('cadastro', $parametros))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('acesso/cadastro')
        ->where('planoInicial', null)
        ->where('cobrancaInicial', null));
})->with([
    'inválidos' => [['plano' => 'inexistente', 'cobranca' => 'gratis']],
    'ausentes' => [[]],
    'listas inválidas' => [['plano' => ['rede'], 'cobranca' => ['anual']]],
]);
