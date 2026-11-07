<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

function payload(array $overrides = []): array
{
    return array_merge([
        'tratamento' => 'dra',
        'nome' => 'Beatriz Nogueira',
        'crmv_numero' => '55912',
        'crmv_uf' => 'SP',
        'email' => 'beatriz.nogueira@vetcarejardins.com.br',
        'celular' => '(19) 99510-8842',
        'telefone_critico' => '(19) 3869-2210',
        'especialidade' => 'Endocrinologia',
    ], $overrides);
}

it('mostra o formulário de cadastro', function (): void {
    actingAs(User::factory()->create())
        ->get(route('lab.veterinarios.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('lab/veterinarios/create')->has('ufs', 27));
});

it('cadastra um veterinário', function (): void {
    actingAs(User::factory()->create())
        ->post(route('lab.veterinarios.store'), payload())
        ->assertRedirect(route('lab.veterinarios.create'));

    assertDatabaseHas('veterinarios', [
        'crmv_uf' => 'SP',
        'crmv_numero' => '55912',
        'celular' => '19995108842',
    ]);
});

it('avisa quando o CRMV já existe', function (): void {
    $user = User::factory()->create();
    actingAs($user)->post(route('lab.veterinarios.store'), payload());

    actingAs($user)
        ->post(route('lab.veterinarios.store'), payload(['email' => 'outro@exemplo.com.br']))
        ->assertSessionHasErrors(['crmv_numero' => 'O CRMV-SP 55912 já está cadastrado.']);
});

it('exige login', function (): void {
    $this->post(route('lab.veterinarios.store'), payload())->assertRedirect(route('login'));
});
