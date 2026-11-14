<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('abre para visitante com os três planos e seus preços e limites', function (): void {
    $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('site/inicio')
        ->where('planos', [
            ['value' => 'bancada', 'nome' => 'Bancada', 'mensal' => 24000, 'anual' => 240000, 'pedidos' => 600, 'usuarios' => 3, 'unidades' => 1],
            ['value' => 'laboratorio', 'nome' => 'Laboratório', 'mensal' => 54000, 'anual' => 540000, 'pedidos' => 2000, 'usuarios' => 10, 'unidades' => 1],
            ['value' => 'rede', 'nome' => 'Rede', 'mensal' => 114000, 'anual' => 1140000, 'pedidos' => 6000, 'usuarios' => null, 'unidades' => 5],
        ]));
});

it('abre também para usuário autenticado', function (): void {
    $usuario = User::factory()->create();
    $this->actingAs($usuario)->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('site/inicio')
        ->where('auth.user.id', $usuario->id)
        ->has('planos', 3));
});
