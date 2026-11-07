<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('mostra a tela de login com o endereço do acesso do veterinário', function (): void {
    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/login')
            ->where('vetUrl', 'https://vet.vidralab.com.br'));
});

it('entra com e-mail e senha corretos', function (): void {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

it('recusa senha errada com a mensagem em português', function (): void {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'senha-errada'])
        ->assertSessionHasErrors(['email' => 'E-mail ou senha não conferem.']);

    $this->assertGuest();
});

it('bloqueia o acesso depois de 5 tentativas erradas', function (): void {
    $user = User::factory()->create();

    foreach (range(1, 5) as $_) {
        $this->post('/login', ['email' => $user->email, 'password' => 'senha-errada']);
    }

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Muitas tentativas. Tente de novo em 1 minuto.']);

    $this->assertGuest();
});

it('guarda o token de "manter conectado" quando pedido', function (): void {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => true]);

    expect($user->fresh()->remember_token)->not->toBeNull();
});

it('sai da conta', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect('/');

    $this->assertGuest();
});
