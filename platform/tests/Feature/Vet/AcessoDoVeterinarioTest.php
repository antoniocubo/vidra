<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Tests\Support\Laboratorios;
use Tests\Support\Veterinarios;

it('manda quem não entrou para o login do veterinário, não o da equipe', function (): void {
    $this->get(route('vet.inicio'))->assertRedirect(route('vet.entrar'));
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('entra com e-mail e senha e cai direto no único laboratório', function (): void {
    $lab = Laboratorios::criar();
    $conta = Veterinarios::comContaEm([$lab]);

    $this->post(route('vet.entrar.store'), ['email' => strtoupper($conta->email), 'senha' => Veterinarios::SENHA])->assertRedirect(route('vet.inicio'));
    $this->assertAuthenticatedAs($conta, 'vet');
    $this->get(route('vet.inicio'))->assertOk()->assertSessionHas('vet_laboratorio_id', $lab);
});

it('recusa senha errada e bloqueia depois de cinco tentativas', function (): void {
    $conta = Veterinarios::comContaEm([Laboratorios::criar()]);

    foreach (range(1, 5) as $_) {
        $this->post(route('vet.entrar.store'), ['email' => $conta->email, 'senha' => 'errada'])->assertSessionHasErrors(['email' => 'E-mail ou senha não conferem.']);
    }
    $this->post(route('vet.entrar.store'), ['email' => $conta->email, 'senha' => Veterinarios::SENHA])->assertSessionHasErrors('email');
    $this->assertGuest('vet');
});

it('separa a conta do veterinário da conta da equipe', function (): void {
    $lab = Laboratorios::criar();
    $equipe = Laboratorios::usuarioEm($lab);
    $conta = Veterinarios::comContaEm([$lab]);

    $this->actingAs($equipe)->get(route('vet.inicio'))->assertRedirect(route('vet.entrar'));
    auth()->guard('web')->logout();

    // Login real: actingAs(..., 'vet') trocaria o guard padrão do teste inteiro.
    $this->post(route('vet.entrar.store'), ['email' => $conta->email, 'senha' => Veterinarios::SENHA]);
    $this->assertAuthenticatedAs($conta, 'vet');
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('pede a escolha com dois laboratórios e só deixa entrar nos vinculados', function (): void {
    $alfa = Laboratorios::criar('Alfa');
    $beta = Laboratorios::criar('Beta');
    $outro = Laboratorios::criar('Outro');
    $conta = Veterinarios::comContaEm([$beta, $alfa]);

    $this->actingAs($conta, 'vet')->get(route('vet.inicio'))->assertRedirect(route('vet.laboratorio.escolher'));
    $this->get(route('vet.laboratorio.escolher'))->assertInertia(fn ($page) => $page->component('vet/escolher-laboratorio')
        ->where('laboratorios', [['id' => $alfa, 'nome' => 'Alfa'], ['id' => $beta, 'nome' => 'Beta']]));
    $this->post(route('vet.laboratorio.entrar'), ['laboratorio_id' => $outro])->assertSessionHasErrors('laboratorio_id');
    $this->post(route('vet.laboratorio.entrar'), ['laboratorio_id' => $beta])->assertRedirect(route('vet.inicio'));
    $this->get(route('vet.inicio'))->assertOk()->assertSessionHas('vet_laboratorio_id', $beta);
});

it('perde o acesso na hora em que o laboratório desfaz o vínculo', function (): void {
    $lab = Laboratorios::criar();
    $conta = Veterinarios::comContaEm([$lab]);
    DB::connection(config()->string('vidra.conexao_sistema'))->table('laboratorio_veterinario')->where('veterinario_id', $conta->veterinario_id)->delete();

    $this->actingAs($conta, 'vet')->withSession(['vet_laboratorio_id' => $lab])->get(route('vet.inicio'))->assertForbidden();
});

it('sai e invalida a sessão', function (): void {
    $conta = Veterinarios::comContaEm([Laboratorios::criar()]);

    $this->actingAs($conta, 'vet')->post(route('vet.sair'))->assertRedirect(route('vet.entrar'));
    $this->assertGuest('vet');
});
