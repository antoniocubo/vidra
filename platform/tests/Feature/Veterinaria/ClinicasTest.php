<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\Laboratorios;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;
use Vidra\Veterinaria\Infrastructure\Persistence\ClinicaModel;
use Vidra\Veterinaria\Infrastructure\Persistence\ClinicaVeterinarioModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

function payloadClinica(array $overrides = []): array
{
    return array_merge([
        'nome_fantasia' => 'Clínica Patas',
        'razao_social' => 'Patas Clínica Veterinária Ltda',
        'cnpj' => '11.222.333/0001-81',
        'cidade' => 'Campinas',
        'bairro' => 'Cambuí',
        'email_laudos' => 'laudos@clinicapatas.com.br',
        'email_faturamento' => 'financeiro@clinicapatas.com.br',
        'telefone_critico' => '(19) 3241-0099',
        'responsavel' => 'Dr. Rafael Lima',
        'observacao_coleta' => 'Motoboy, rota Norte, 10h e 15h',
        'desconto' => '10',
        'dia_fechamento' => '31',
        'dia_vencimento' => '10',
        'forma_pagamento' => 'pix',
    ], $overrides);
}

function clinicaCadastrada(string $laboratorio, array $overrides = []): string
{
    $id = (string) Illuminate\Support\Str::uuid7();
    DB::connection(config()->string('vidra.conexao_sistema'))->table('clinicas')->insert(array_merge([
        'id' => $id, 'laboratorio_id' => $laboratorio, 'nome_fantasia' => 'Clínica Patas', 'cidade' => 'Campinas',
        'email_faturamento' => 'financeiro@exemplo.com.br', 'desconto' => 0, 'dia_fechamento' => 31, 'dia_vencimento' => 10,
        'forma_pagamento' => 'pix', 'ativa' => true, 'created_at' => now(), 'updated_at' => now(),
    ], $overrides));

    return $id;
}

it('exige login', function (): void {
    $this->get(route('lab.clinicas.index'))->assertRedirect(route('login'));
    $this->post(route('lab.clinicas.store'), payloadClinica())->assertRedirect(route('login'));
});

it('mostra o formulário de nova clínica', function (): void {
    actingAs(Laboratorios::usuario())->get(route('lab.clinicas.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('lab/clinicas/create')->where('formasDePagamento', ['pix', 'boleto', 'transferencia']));
});

it('cadastra uma clínica no laboratório do usuário e abre o detalhe dela', function (): void {
    $laboratorio = Laboratorios::criar();

    $resposta = actingAs(Laboratorios::usuarioEm($laboratorio))->post(route('lab.clinicas.store'), payloadClinica());

    $id = DB::table('clinicas')->value('id');
    $resposta->assertRedirect(route('lab.clinicas.index', ['clinica' => $id]))->assertSessionHas('success', 'Clínica cadastrada.');
    assertDatabaseHas('clinicas', [
        'laboratorio_id' => $laboratorio, 'nome_fantasia' => 'Clínica Patas', 'cnpj' => '11222333000181', 'telefone_critico' => '1932410099',
        'email_faturamento' => 'financeiro@clinicapatas.com.br', 'desconto' => 10, 'dia_fechamento' => 31, 'dia_vencimento' => 10,
        'forma_pagamento' => 'pix', 'ativa' => true,
    ]);
});

it('cadastra só com os campos obrigatórios', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.clinicas.store'), [
        'nome_fantasia' => 'Consultório Dra. Lia Prado', 'cidade' => 'Hortolândia', 'email_faturamento' => 'lia@exemplo.com.br',
        'desconto' => '0', 'dia_fechamento' => '15', 'dia_vencimento' => '5', 'forma_pagamento' => 'boleto',
    ])->assertSessionHasNoErrors();

    assertDatabaseHas('clinicas', ['nome_fantasia' => 'Consultório Dra. Lia Prado', 'cnpj' => null, 'dia_fechamento' => 15]);
});

it('não deixa repetir o CNPJ no mesmo laboratório', function (): void {
    $usuario = Laboratorios::usuario();
    actingAs($usuario)->post(route('lab.clinicas.store'), payloadClinica())->assertSessionHasNoErrors();

    actingAs($usuario)->post(route('lab.clinicas.store'), payloadClinica(['nome_fantasia' => 'Outra', 'cnpj' => '11222333000181']))
        ->assertSessionHasErrors(['cnpj' => 'Já existe uma clínica com este CNPJ: Clínica Patas.']);
    $this->assertDatabaseCount('clinicas', 1);
});

it('deixa outro laboratório cadastrar o mesmo CNPJ', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.clinicas.store'), payloadClinica())->assertSessionHasNoErrors();
    actingAs(Laboratorios::usuario())->post(route('lab.clinicas.store'), payloadClinica())->assertSessionHasNoErrors();

    $this->assertDatabaseCount('clinicas', 2);
});

it('valida o formato do cadastro', function (string $campo, mixed $valor): void {
    actingAs(Laboratorios::usuario())->post(route('lab.clinicas.store'), payloadClinica([$campo => $valor]))->assertSessionHasErrors($campo);
    $this->assertDatabaseCount('clinicas', 0);
})->with([
    ['nome_fantasia', ''], ['cidade', ''], ['email_faturamento', ''], ['email_faturamento', 'nao-e-email'],
    ['cnpj', '11222333000180'], ['cnpj', '123'], ['telefone_critico', '123'],
    ['desconto', '101'], ['desconto', '-1'], ['desconto', 'dez'],
    ['dia_fechamento', '29'], ['dia_fechamento', '0'], ['dia_vencimento', '29'], ['dia_vencimento', '0'], ['forma_pagamento', 'cartao'],
]);

it('lista as clínicas ativas com a primeira selecionada', function (): void {
    $laboratorio = Laboratorios::criar();
    clinicaCadastrada($laboratorio, ['nome_fantasia' => 'Hospital Vet Bicho Feliz', 'desconto' => 15]);
    clinicaCadastrada($laboratorio, ['nome_fantasia' => 'Clínica Amigo Fiel']);
    clinicaCadastrada($laboratorio, ['nome_fantasia' => 'Antiga Clínica', 'ativa' => false]);

    actingAs(Laboratorios::usuarioEm($laboratorio))->get(route('lab.clinicas.index'))->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('lab/clinicas/index')
            ->has('clinicas', 2)
            ->where('clinicas.0.nomeFantasia', 'Clínica Amigo Fiel')
            ->where('clinicas.1.desconto', 15)
            ->where('selecionado.nomeFantasia', 'Clínica Amigo Fiel')
            ->where('filtros.inativas', false),
    );
});

it('inclui as inativas e busca por nome, CNPJ ou cidade', function (): void {
    $laboratorio = Laboratorios::criar();
    clinicaCadastrada($laboratorio, ['nome_fantasia' => 'Antiga Clínica', 'cidade' => 'Hortolândia', 'ativa' => false]);
    clinicaCadastrada($laboratorio, ['nome_fantasia' => 'Clínica Amigo Fiel', 'cnpj' => '11444777000161', 'cidade' => 'Sumaré']);
    $usuario = Laboratorios::usuarioEm($laboratorio);

    actingAs($usuario)->get(route('lab.clinicas.index', ['inativas' => 1]))->assertInertia(fn (Assert $page) => $page->has('clinicas', 2));
    actingAs($usuario)->get(route('lab.clinicas.index', ['inativas' => 1, 'busca' => 'hortolandia']))->assertInertia(fn (Assert $page) => $page->has('clinicas', 1)->where('clinicas.0.ativa', false));
    actingAs($usuario)->get(route('lab.clinicas.index', ['busca' => '11.444.777']))->assertInertia(fn (Assert $page) => $page->has('clinicas', 1)->where('clinicas.0.nomeFantasia', 'Clínica Amigo Fiel'));
    actingAs($usuario)->get(route('lab.clinicas.index', ['busca' => 'xyz']))->assertInertia(fn (Assert $page) => $page->has('clinicas', 0)->where('selecionado', null));
});

it('edita e atualiza a clínica', function (): void {
    $laboratorio = Laboratorios::criar();
    $id = clinicaCadastrada($laboratorio);
    $usuario = Laboratorios::usuarioEm($laboratorio);

    actingAs($usuario)->get(route('lab.clinicas.edit', $id))->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('lab/clinicas/edit')->where('clinica.id', $id)->where('clinica.diaDeFechamento', 31),
    );

    actingAs($usuario)->put(route('lab.clinicas.update', $id), payloadClinica(['nome_fantasia' => 'Clínica Patas Centro', 'desconto' => '15', 'dia_fechamento' => '20']))
        ->assertRedirect(route('lab.clinicas.index', ['clinica' => $id]))
        ->assertSessionHas('success', 'Clínica atualizada.');
    assertDatabaseHas('clinicas', ['id' => $id, 'nome_fantasia' => 'Clínica Patas Centro', 'desconto' => 15, 'dia_fechamento' => 20, 'cnpj' => '11222333000181']);
});

it('deixa a clínica manter o próprio CNPJ ao salvar, mas não pegar o de outra', function (): void {
    $laboratorio = Laboratorios::criar();
    $patas = clinicaCadastrada($laboratorio, ['cnpj' => '11222333000181']);
    clinicaCadastrada($laboratorio, ['nome_fantasia' => 'Amigo Fiel', 'cnpj' => '11444777000161']);
    $usuario = Laboratorios::usuarioEm($laboratorio);

    actingAs($usuario)->put(route('lab.clinicas.update', $patas), payloadClinica())->assertSessionHasNoErrors();
    actingAs($usuario)->put(route('lab.clinicas.update', $patas), payloadClinica(['cnpj' => '11444777000161']))
        ->assertSessionHasErrors(['cnpj' => 'Já existe uma clínica com este CNPJ: Amigo Fiel.']);
});

it('desativa e reativa a clínica', function (): void {
    $laboratorio = Laboratorios::criar();
    $id = clinicaCadastrada($laboratorio);
    $usuario = Laboratorios::usuarioEm($laboratorio);

    actingAs($usuario)->post(route('lab.clinicas.desativar', $id))
        ->assertRedirect(route('lab.clinicas.index', ['clinica' => $id, 'inativas' => 1]))->assertSessionHas('success', 'Clínica desativada.');
    assertDatabaseHas('clinicas', ['id' => $id, 'ativa' => false]);

    actingAs($usuario)->post(route('lab.clinicas.reativar', $id))->assertSessionHas('success', 'Clínica reativada.');
    assertDatabaseHas('clinicas', ['id' => $id, 'ativa' => true]);
});

it('responde 404 para a clínica de outro laboratório em todas as ações', function (): void {
    $alheia = clinicaCadastrada(Laboratorios::criar('Beta'));
    $usuario = Laboratorios::usuario();

    actingAs($usuario)->get(route('lab.clinicas.index', ['clinica' => $alheia]))->assertNotFound();
    actingAs($usuario)->get(route('lab.clinicas.edit', $alheia))->assertNotFound();
    actingAs($usuario)->put(route('lab.clinicas.update', $alheia), payloadClinica())->assertNotFound();
    actingAs($usuario)->post(route('lab.clinicas.desativar', $alheia))->assertNotFound();
    actingAs($usuario)->post(route('lab.clinicas.reativar', $alheia))->assertNotFound();
    assertDatabaseHas('clinicas', ['id' => $alheia, 'nome_fantasia' => 'Clínica Patas', 'ativa' => true]);
});

it('não mostra a clínica nem a ligação com veterinário de um laboratório dentro do outro', function (): void {
    $alfa = Laboratorios::criar('Alfa');
    $beta = Laboratorios::criar('Beta');
    $clinicaDoAlfa = clinicaCadastrada($alfa);
    clinicaCadastrada($beta, ['nome_fantasia' => 'Clínica do Beta']);
    actingAs(Laboratorios::usuarioEm($alfa))->post(route('lab.veterinarios.store'), [
        'tratamento' => 'dra', 'nome' => 'Beatriz Nogueira', 'crmv_numero' => '55912', 'crmv_uf' => 'SP', 'email' => 'bea@exemplo.com.br',
        'celular' => '19995108842', 'telefone_critico' => '1932410099', 'quem_paga' => 'clinica', 'recebe_laudo' => 'cada_laudo',
        've_pedidos_dos_colegas' => false, 'clinica_ids' => [$clinicaDoAlfa],
    ])->assertSessionHasNoErrors();
    $contexto = app(ContextoLaboratorio::class);

    $contexto->executarComo($alfa, function (): void {
        expect(ClinicaModel::query()->pluck('nome_fantasia')->all())->toBe(['Clínica Patas'])
            ->and(ClinicaVeterinarioModel::query()->count())->toBe(1);
    });
    $contexto->executarComo($beta, function (): void {
        expect(ClinicaModel::query()->pluck('nome_fantasia')->all())->toBe(['Clínica do Beta'])
            ->and(ClinicaVeterinarioModel::query()->count())->toBe(0);
    });
});
