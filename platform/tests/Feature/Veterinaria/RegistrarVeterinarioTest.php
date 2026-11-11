<?php

declare(strict_types=1);

use Tests\Support\Laboratorios;

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
        'quem_paga' => 'clinica',
        'recebe_laudo' => 'cada_laudo',
        've_pedidos_dos_colegas' => false,
        'especialidade' => 'Endocrinologia',
    ], $overrides);
}

it('mostra o formulário de cadastro', function (): void {
    actingAs(Laboratorios::usuario())
        ->get(route('lab.veterinarios.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('lab/veterinarios/create')->has('ufs', 27));
});

it('cadastra um veterinário', function (): void {
    actingAs(Laboratorios::usuario())
        ->post(route('lab.veterinarios.store'), payload())
        ->assertRedirect(route('lab.veterinarios.index', ['veterinario' => Illuminate\Support\Facades\DB::table('laboratorio_veterinario')->value('id')]));

    assertDatabaseHas('veterinarios', [
        'crmv_uf' => 'SP',
        'crmv_numero' => '55912',
        'celular' => '19995108842',
    ]);
});

it('avisa quando o vínculo já existe', function (): void {
    $user = Laboratorios::usuario();
    actingAs($user)->post(route('lab.veterinarios.store'), payload());

    actingAs($user)
        ->post(route('lab.veterinarios.store'), payload(['email' => 'outro@exemplo.com.br']))
        ->assertSessionHasErrors(['crmv_numero' => 'Este veterinário já está cadastrado no seu laboratório.']);
});

it('exige login', function (): void {
    $this->post(route('lab.veterinarios.store'), payload())->assertRedirect(route('login'));
});

it('grava o vínculo com as preferências no laboratório do usuário', function (): void {
    $laboratorio = Laboratorios::criar();
    actingAs(Laboratorios::usuarioEm($laboratorio))->post(route('lab.veterinarios.store'), payload())
        ->assertSessionHas('success', 'Veterinário cadastrado.');
    assertDatabaseHas('laboratorio_veterinario', [
        'laboratorio_id' => $laboratorio,
        'veterinario_id' => Illuminate\Support\Facades\DB::table('veterinarios')->value('id'),
        'telefone_critico' => '1938692210', 'quem_paga' => 'clinica',
        'recebe_laudo' => 'cada_laudo', 've_pedidos_dos_colegas' => false,
    ]);
});

it('reaproveita o veterinário de outro laboratório e isola os vínculos', function (): void {
    $alfa = Laboratorios::criar('Alfa');
    $beta = Laboratorios::criar('Beta');
    actingAs(Laboratorios::usuarioEm($alfa))->post(route('lab.veterinarios.store'), payload())->assertSessionHasNoErrors();
    $contexto = app(Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio::class);
    $contexto->executarComo($beta, function (): void {
        expect(Vidra\Veterinaria\Infrastructure\Persistence\VinculoModel::query()->count())->toBe(0);
    });
    actingAs(Laboratorios::usuarioEm($beta))->post(route('lab.veterinarios.store'), payload([
        'nome' => 'Outro Nome', 'email' => 'outro@exemplo.com', 'telefone_critico' => '11988887777',
        'quem_paga' => 'tutor', 'recebe_laudo' => 'cada_laudo', 've_pedidos_dos_colegas' => true,
    ]))->assertSessionHasNoErrors()->assertSessionHas('success', 'Dra. Beatriz Nogueira já estava no Vidra e agora faz parte do seu laboratório.');
    $this->assertDatabaseCount('veterinarios', 1);
    $this->assertDatabaseCount('laboratorio_veterinario', 2);
    assertDatabaseHas('veterinarios', ['nome' => 'Beatriz Nogueira', 'email' => payload()['email']]);
    foreach ([$alfa => '1938692210', $beta => '11988887777'] as $lab => $telefone) {
        $contexto->executarComo($lab, function () use ($lab, $telefone): void {
            $vinculos = Vidra\Veterinaria\Infrastructure\Persistence\VinculoModel::query()->get();
            expect($vinculos)->toHaveCount(1)
                ->and($vinculos->first()->laboratorio_id)->toBe($lab)
                ->and($vinculos->first()->telefone_critico)->toBe($telefone);
        });
    }
    assertDatabaseHas('laboratorio_veterinario', ['laboratorio_id' => $beta, 'quem_paga' => 'tutor', 'recebe_laudo' => 'cada_laudo', 've_pedidos_dos_colegas' => true]);
});

it('valida as preferências do vínculo', function (string $campo, mixed $valor): void {
    actingAs(Laboratorios::usuario())->post(route('lab.veterinarios.store'), payload([$campo => $valor]))->assertSessionHasErrors($campo);
    $this->assertDatabaseCount('veterinarios', 0);
    $this->assertDatabaseCount('laboratorio_veterinario', 0);
})->with([['quem_paga', 'invalido'], ['recebe_laudo', 'invalido'], ['ve_pedidos_dos_colegas', 'invalido']]);

it('desfaz o cadastro global quando a gravação do vínculo falha', function (): void {
    $repository = Mockery::mock(Vidra\Veterinaria\Domain\Vinculo\VinculoRepository::class);
    $repository->shouldReceive('nextIdentity')->once()->andReturn(new Vidra\Veterinaria\Domain\Vinculo\VinculoId((string) Illuminate\Support\Str::uuid7()));
    $repository->shouldReceive('save')->once()->andThrow(new RuntimeException('Falha de persistência'));
    $this->app->instance(Vidra\Veterinaria\Domain\Vinculo\VinculoRepository::class, $repository);
    $this->withoutExceptionHandling();
    try {
        actingAs(Laboratorios::usuario())->post(route('lab.veterinarios.store'), payload());
        test()->fail('Esperava falha de persistência');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('Falha de persistência');
    }
    $this->assertDatabaseCount('veterinarios', 0);
    $this->assertDatabaseCount('laboratorio_veterinario', 0);
});
