<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\Laboratorios;

use function Pest\Laravel\actingAs;

function dadosDoTutor(array $extra = []): array
{
    return array_replace([
        'nome' => 'Fernanda Rocha', 'celular' => '(19) 99733-1208', 'email' => 'fernanda@example.com', 'cpf' => '529.982.247-25', 'cep' => '13010-000', 'autoriza_email' => true,
        'animais' => [['nome' => 'Amora', 'especie' => 'cao', 'raca' => 'SRD', 'sexo' => 'femea', 'modo_idade' => 'data', 'nascimento' => '2020-11-08', 'peso' => '12,4', 'castrado' => 'sim', 'microchip' => '123456789012345', 'vinculo_veterinario_id' => null]],
    ], $extra);
}

it('oferece voltar ao pedido no cadastro do tutor', function (): void {
    actingAs(Laboratorios::usuario())->get(route('lab.tutores.create', ['voltar' => 'pedido']))->assertInertia(fn (Assert $page) => $page->where('voltar', 'pedido'));
    actingAs(Laboratorios::usuario())->get(route('lab.tutores.create', ['voltar' => 'outra-coisa']))->assertInertia(fn (Assert $page) => $page->where('voltar', ''));
});

it('volta ao novo pedido com o primeiro animal escolhido depois de cadastrar o tutor', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.tutores.store'), dadosDoTutor(['voltar' => 'pedido']))->assertSessionHasNoErrors()
        ->assertRedirect(route('lab.pedidos.create', ['animal' => DB::table('animais')->value('id')]));
});

it('segue para a lista de tutores quando não veio do pedido', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.tutores.store'), dadosDoTutor())->assertRedirect(route('lab.tutores.index', ['tutor' => DB::table('tutores')->value('id')]));
});
