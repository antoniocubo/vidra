<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\Laboratorios;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

function payloadVeterinario(array $overrides = []): array
{
    return array_merge([
        'tratamento' => 'dra', 'nome' => 'Beatriz Nogueira', 'crmv_numero' => '55912', 'crmv_uf' => 'SP',
        'email' => 'beatriz.nogueira@vetcarejardins.com.br', 'celular' => '(19) 99510-8842', 'telefone_critico' => '(19) 3869-2210',
        'quem_paga' => 'clinica', 'recebe_laudo' => 'cada_laudo', 've_pedidos_dos_colegas' => false, 'especialidade' => 'Endocrinologia',
    ], $overrides);
}

function clinicaDoLaboratorio(string $laboratorio, string $nome, bool $ativa = true): string
{
    $id = (string) Illuminate\Support\Str::uuid7();
    DB::connection(config()->string('vidra.conexao_sistema'))->table('clinicas')->insert([
        'id' => $id, 'laboratorio_id' => $laboratorio, 'nome_fantasia' => $nome, 'cidade' => 'Campinas',
        'email_faturamento' => 'f@exemplo.com.br', 'desconto' => 0, 'dia_fechamento' => 31, 'dia_vencimento' => 10,
        'forma_pagamento' => 'pix', 'ativa' => $ativa, 'created_at' => now(), 'updated_at' => now(),
    ]);

    return $id;
}

function vinculoDe(string $laboratorio): string
{
    return (string) DB::connection(config()->string('vidra.conexao_sistema'))->table('laboratorio_veterinario')->where('laboratorio_id', $laboratorio)->value('id');
}

it('cadastra o veterinário marcando onde atende e abre o detalhe dele na lista', function (): void {
    $laboratorio = Laboratorios::criar();
    $patas = clinicaDoLaboratorio($laboratorio, 'Clínica Patas');
    $amigo = clinicaDoLaboratorio($laboratorio, 'Clínica Amigo Fiel');

    $resposta = actingAs(Laboratorios::usuarioEm($laboratorio))->post(route('lab.veterinarios.store'), payloadVeterinario(['clinica_ids' => [$patas, $amigo]]));

    $vinculo = vinculoDe($laboratorio);
    $resposta->assertRedirect(route('lab.veterinarios.index', ['veterinario' => $vinculo]))->assertSessionHas('success', 'Veterinário cadastrado.');
    assertDatabaseHas('clinica_veterinario', ['laboratorio_id' => $laboratorio, 'vinculo_id' => $vinculo, 'clinica_id' => $patas]);
    assertDatabaseHas('clinica_veterinario', ['laboratorio_id' => $laboratorio, 'vinculo_id' => $vinculo, 'clinica_id' => $amigo]);
});

it('cadastra sem clínica', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.veterinarios.store'), payloadVeterinario())->assertSessionHasNoErrors();

    $this->assertDatabaseCount('clinica_veterinario', 0);
});

it('recusa clínica de outro laboratório ao cadastrar e desfaz o cadastro', function (): void {
    $alheia = clinicaDoLaboratorio(Laboratorios::criar('Beta'), 'Clínica do Beta');

    actingAs(Laboratorios::usuario())->post(route('lab.veterinarios.store'), payloadVeterinario(['clinica_ids' => [$alheia]]))
        ->assertSessionHasErrors(['clinica_ids' => 'Clínica não encontrada.']);
    $this->assertDatabaseCount('veterinarios', 0);
    $this->assertDatabaseCount('laboratorio_veterinario', 0);
});

it('oferece só as clínicas ativas no formulário de cadastro', function (): void {
    $laboratorio = Laboratorios::criar();
    clinicaDoLaboratorio($laboratorio, 'Clínica Patas');
    clinicaDoLaboratorio($laboratorio, 'Antiga Clínica', false);
    clinicaDoLaboratorio(Laboratorios::criar('Beta'), 'Clínica do Beta');

    actingAs(Laboratorios::usuarioEm($laboratorio))->get(route('lab.veterinarios.create'))->assertInertia(
        fn (Assert $page) => $page->component('lab/veterinarios/create')->has('clinicas', 1)->where('clinicas.0.nome', 'Clínica Patas'),
    );
});

it('lista os veterinários do laboratório por nome com a clínica e o pagador', function (): void {
    $laboratorio = Laboratorios::criar();
    $patas = clinicaDoLaboratorio($laboratorio, 'Clínica Patas');
    $usuario = Laboratorios::usuarioEm($laboratorio);
    actingAs($usuario)->post(route('lab.veterinarios.store'), payloadVeterinario(['clinica_ids' => [$patas]]));
    actingAs($usuario)->post(route('lab.veterinarios.store'), payloadVeterinario(['nome' => 'Ana Ribeiro', 'crmv_numero' => '38877', 'email' => 'ana@exemplo.com.br', 'quem_paga' => 'tutor']));

    actingAs($usuario)->get(route('lab.veterinarios.index'))->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('lab/veterinarios/index')
            ->has('veterinarios', 2)
            ->where('veterinarios.0.nome', 'Dra. Ana Ribeiro')
            ->where('veterinarios.0.quemPaga', 'tutor')
            ->where('veterinarios.0.clinicas', [])
            ->where('veterinarios.1.crmv', 'CRMV-SP 55912')
            ->where('veterinarios.1.clinicas.0.nome', 'Clínica Patas')
            ->where('selecionado.nome', 'Dra. Ana Ribeiro'),
    );
});

it('seleciona o veterinário da URL e busca por nome, CRMV ou clínica', function (): void {
    $laboratorio = Laboratorios::criar();
    $patas = clinicaDoLaboratorio($laboratorio, 'Clínica Patas');
    $usuario = Laboratorios::usuarioEm($laboratorio);
    actingAs($usuario)->post(route('lab.veterinarios.store'), payloadVeterinario(['clinica_ids' => [$patas]]));
    actingAs($usuario)->post(route('lab.veterinarios.store'), payloadVeterinario(['nome' => 'Ana Ribeiro', 'crmv_numero' => '38877', 'email' => 'ana@exemplo.com.br']));
    $bea = DB::table('laboratorio_veterinario')->join('veterinarios', 'veterinarios.id', '=', 'veterinario_id')->where('crmv_numero', '55912')->value('laboratorio_veterinario.id');

    actingAs($usuario)->get(route('lab.veterinarios.index', ['veterinario' => $bea]))->assertInertia(
        fn (Assert $page) => $page->where('selecionado.nome', 'Dra. Beatriz Nogueira')->where('selecionado.telefoneCritico', '(19) 3869-2210')->has('veterinarios', 2),
    );
    foreach (['beatriz' => 'Dra. Beatriz Nogueira', '38877' => 'Dra. Ana Ribeiro', 'patas' => 'Dra. Beatriz Nogueira'] as $busca => $nome) {
        actingAs($usuario)->get(route('lab.veterinarios.index', ['busca' => $busca]))->assertInertia(
            fn (Assert $page) => $page->has('veterinarios', 1)->where('veterinarios.0.nome', $nome),
        );
    }
});

it('mostra a clínica com os veterinários que pedem por ela', function (): void {
    $laboratorio = Laboratorios::criar();
    $patas = clinicaDoLaboratorio($laboratorio, 'Clínica Patas');
    $usuario = Laboratorios::usuarioEm($laboratorio);
    actingAs($usuario)->post(route('lab.veterinarios.store'), payloadVeterinario(['clinica_ids' => [$patas]]));

    actingAs($usuario)->get(route('lab.clinicas.index', ['clinica' => $patas]))->assertInertia(
        fn (Assert $page) => $page->where('selecionado.veterinarios.0.nome', 'Dra. Beatriz Nogueira')->where('selecionado.veterinarios.0.vinculoId', vinculoDe($laboratorio)),
    );
});

it('edita o vínculo: pagador, telefone crítico, laudos, colegas e onde atende', function (): void {
    $laboratorio = Laboratorios::criar();
    $patas = clinicaDoLaboratorio($laboratorio, 'Clínica Patas');
    $amigo = clinicaDoLaboratorio($laboratorio, 'Clínica Amigo Fiel');
    $usuario = Laboratorios::usuarioEm($laboratorio);
    actingAs($usuario)->post(route('lab.veterinarios.store'), payloadVeterinario(['clinica_ids' => [$patas]]));
    $vinculo = vinculoDe($laboratorio);

    actingAs($usuario)->get(route('lab.veterinarios.edit', $vinculo))->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('lab/veterinarios/edit')->where('veterinario.vinculoId', $vinculo)->has('clinicas', 2)->where('veterinario.clinicas.0.id', $patas),
    );

    actingAs($usuario)->put(route('lab.veterinarios.update', $vinculo), [
        'telefone_critico' => '(19) 98877-1203', 'quem_paga' => 'tutor', 'recebe_laudo' => 'cada_laudo', 've_pedidos_dos_colegas' => true, 'clinica_ids' => [$amigo],
    ])->assertRedirect(route('lab.veterinarios.index', ['veterinario' => $vinculo]))->assertSessionHas('success', 'Cadastro do veterinário atualizado.');

    assertDatabaseHas('laboratorio_veterinario', ['id' => $vinculo, 'telefone_critico' => '19988771203', 'quem_paga' => 'tutor', 'recebe_laudo' => 'cada_laudo', 've_pedidos_dos_colegas' => true]);
    assertDatabaseHas('clinica_veterinario', ['vinculo_id' => $vinculo, 'clinica_id' => $amigo]);
    assertDatabaseMissing('clinica_veterinario', ['vinculo_id' => $vinculo, 'clinica_id' => $patas]);
    assertDatabaseHas('veterinarios', ['nome' => 'Beatriz Nogueira', 'email' => 'beatriz.nogueira@vetcarejardins.com.br']);
});

it('mantém na edição a clínica inativa onde o veterinário já atende', function (): void {
    $laboratorio = Laboratorios::criar();
    $antiga = clinicaDoLaboratorio($laboratorio, 'Antiga Clínica');
    $usuario = Laboratorios::usuarioEm($laboratorio);
    actingAs($usuario)->post(route('lab.veterinarios.store'), payloadVeterinario(['clinica_ids' => [$antiga]]));
    DB::table('clinicas')->where('id', $antiga)->update(['ativa' => false]);

    actingAs($usuario)->get(route('lab.veterinarios.edit', vinculoDe($laboratorio)))->assertInertia(
        fn (Assert $page) => $page->has('clinicas', 1)->where('clinicas.0.ativa', false),
    );
});

it('valida a edição do vínculo', function (array $troca, string $campoComErro): void {
    $laboratorio = Laboratorios::criar();
    $usuario = Laboratorios::usuarioEm($laboratorio);
    actingAs($usuario)->post(route('lab.veterinarios.store'), payloadVeterinario());

    actingAs($usuario)->put(route('lab.veterinarios.update', vinculoDe($laboratorio)), [
        'telefone_critico' => '1938692210', 'quem_paga' => 'clinica', 'recebe_laudo' => 'cada_laudo', 've_pedidos_dos_colegas' => false, ...$troca,
    ])->assertSessionHasErrors($campoComErro);
})->with([
    [['quem_paga' => 'invalido'], 'quem_paga'], [['recebe_laudo' => 'invalido'], 'recebe_laudo'], [['telefone_critico' => ''], 'telefone_critico'],
    [['clinica_ids' => 'texto'], 'clinica_ids'], [['clinica_ids' => ['nao-e-uuid']], 'clinica_ids.0'],
]);

it('recusa na edição uma clínica de outro laboratório', function (): void {
    $laboratorio = Laboratorios::criar();
    $alheia = clinicaDoLaboratorio(Laboratorios::criar('Beta'), 'Clínica do Beta');
    $usuario = Laboratorios::usuarioEm($laboratorio);
    actingAs($usuario)->post(route('lab.veterinarios.store'), payloadVeterinario());

    actingAs($usuario)->put(route('lab.veterinarios.update', vinculoDe($laboratorio)), [
        'telefone_critico' => '1938692210', 'quem_paga' => 'clinica', 'recebe_laudo' => 'cada_laudo', 've_pedidos_dos_colegas' => false, 'clinica_ids' => [$alheia],
    ])->assertSessionHasErrors(['clinica_ids' => 'Clínica não encontrada.']);
    $this->assertDatabaseCount('clinica_veterinario', 0);
});

it('responde 404 para o vínculo de outro laboratório', function (): void {
    $beta = Laboratorios::criar('Beta');
    actingAs(Laboratorios::usuarioEm($beta))->post(route('lab.veterinarios.store'), payloadVeterinario())->assertSessionHasNoErrors();
    $alheio = vinculoDe($beta);
    $usuario = Laboratorios::usuario();

    actingAs($usuario)->get(route('lab.veterinarios.index', ['veterinario' => $alheio]))->assertNotFound();
    actingAs($usuario)->get(route('lab.veterinarios.edit', $alheio))->assertNotFound();
    actingAs($usuario)->put(route('lab.veterinarios.update', $alheio), [
        'telefone_critico' => '1938692210', 'quem_paga' => 'tutor', 'recebe_laudo' => 'cada_laudo', 've_pedidos_dos_colegas' => false,
    ])->assertNotFound();
    assertDatabaseHas('laboratorio_veterinario', ['id' => $alheio, 'quem_paga' => 'clinica']);
});

it('lista só os veterinários do próprio laboratório', function (): void {
    $alfa = Laboratorios::criar('Alfa');
    $beta = Laboratorios::criar('Beta');
    actingAs(Laboratorios::usuarioEm($beta))->post(route('lab.veterinarios.store'), payloadVeterinario());

    actingAs(Laboratorios::usuarioEm($alfa))->get(route('lab.veterinarios.index'))->assertInertia(
        fn (Assert $page) => $page->has('veterinarios', 0)->where('selecionado', null),
    );
});
