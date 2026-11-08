<?php

declare(strict_types=1);
use Illuminate\Support\Facades\DB;
use Tests\Support\Laboratorios;
use Tests\Support\TutorRelogioFixo;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;
use Vidra\Veterinaria\Domain\Relogio;
use Vidra\Veterinaria\Infrastructure\Persistence\AnimalModel;
use Vidra\Veterinaria\Infrastructure\Persistence\TutorModel;

use function Pest\Laravel\actingAs;

function animalPayload(array $overrides = []): array
{
    return array_replace(['nome' => 'Amora', 'especie' => 'cao', 'raca' => 'Golden Retriever', 'sexo' => 'femea', 'modo_idade' => 'data', 'nascimento' => '2020-11-08', 'peso' => '12,4', 'castrado' => 'sim', 'microchip' => '123456789012345', 'vinculo_veterinario_id' => null], $overrides);
}
function tutorPayload(array $overrides = []): array
{
    return array_replace(['nome' => 'Fernanda Rocha', 'celular' => '(19) 99733-1208', 'email' => 'fernanda@example.com', 'cpf' => '529.982.247-25', 'cep' => '13010-000', 'autoriza_email' => true, 'animais' => [animalPayload(), animalPayload(['nome' => 'Paçoca', 'especie' => 'gato'])]], $overrides);
}
beforeEach(function (): void {
    $this->app->instance(Relogio::class, new TutorRelogioFixo);
});
it('cadastra tutor com dois animais e registra atendente e hora', function (): void {
    $user = Laboratorios::usuario();
    actingAs($user)->post(route('lab.tutores.store'), tutorPayload())->assertSessionHasNoErrors()->assertSessionHas('success', 'Fernanda Rocha cadastrada.');
    $this->assertDatabaseCount('tutores', 1);
    $this->assertDatabaseCount('animais', 2);
    $this->assertDatabaseHas('tutores', ['celular' => '19997331208', 'cpf' => '52998224725', 'email_autorizado_por' => $user->id, 'email_autorizado_em' => '2026-11-08 17:32:00']);
    $this->assertDatabaseHas('animais', ['nome' => 'Amora', 'peso_gramas' => 12400]);
    $id = DB::table('tutores')->value('id');
    $this->get(route('lab.tutores.index', ['tutor' => $id]))->assertOk()->assertInertia(fn ($page) => $page->component('lab/tutores/index')->where('selecionado.autorizadoPor', $user->name)->where('selecionado.autorizadoEm', '08/11/2026 às 14:32')->has('selecionado.animais', 2));
});
it('não repete celular no mesmo laboratório mas aceita em outro', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.tutores.store'), tutorPayload())->assertSessionHasNoErrors();
    $this->post(route('lab.tutores.store'), tutorPayload())->assertSessionHasErrors(['celular' => 'Já existe um tutor com este celular: Fernanda Rocha.']);
    actingAs(Laboratorios::usuario())->post(route('lab.tutores.store'), tutorPayload())->assertSessionHasNoErrors();
    $this->assertDatabaseCount('tutores', 2);
});
it('busca pelo nome do animal celular com máscara CPF e nome do tutor', function (string $busca): void {
    actingAs(Laboratorios::usuario())->post(route('lab.tutores.store'), tutorPayload())->assertSessionHasNoErrors();
    $this->get(route('lab.tutores.index', ['busca' => $busca]))->assertOk()->assertInertia(fn ($p) => $p->has('tutores', 1)->where('tutores.0.nome', 'Fernanda Rocha'));
})->with(['aMoRa', '(19) 99733-1208', '529.982.247-25', 'fernanda']);
it('retira e registra novamente a autorização sem apagar animais', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.tutores.store'), tutorPayload());
    $id = DB::table('tutores')->value('id');
    $this->delete(route('lab.tutores.autorizacao.destroy', ['tutor' => $id]))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('tutores', ['id' => $id, 'email_autorizado_em' => null, 'email_autorizado_por' => null]);
    $this->post(route('lab.tutores.autorizacao.store', ['tutor' => $id]))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('tutores', ['id' => $id, 'email_autorizado_em' => '2026-11-08 17:32:00']);
    $this->assertDatabaseCount('animais', 2);
});
it('retorna 404 para tutor de outro laboratório em todas as ações', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.tutores.store'), tutorPayload());
    $id = DB::table('tutores')->value('id');
    actingAs(Laboratorios::usuario());
    $this->get(route('lab.tutores.index', ['tutor' => $id]))->assertNotFound();
    $this->get(route('lab.tutores.edit', ['tutor' => $id]))->assertNotFound();
    $this->put(route('lab.tutores.update', ['tutor' => $id]), tutorPayload())->assertNotFound();
    $this->post(route('lab.tutores.autorizacao.store', ['tutor' => $id]))->assertNotFound();
    $this->delete(route('lab.tutores.autorizacao.destroy', ['tutor' => $id]))->assertNotFound();
    $this->get(route('lab.tutores.animais.create', ['tutor' => $id]))->assertNotFound();
    $this->post(route('lab.tutores.animais.store', ['tutor' => $id]), animalPayload())->assertNotFound();
});
it('isola a tabela de tutores por laboratório', function (): void {
    $alfa = Laboratorios::criar();
    $beta = Laboratorios::criar();
    actingAs(Laboratorios::usuarioEm($alfa))->post(route('lab.tutores.store'), tutorPayload());
    app(ContextoLaboratorio::class)->executarComo($beta, fn () => expect(TutorModel::query()->count())->toBe(0));
    app(ContextoLaboratorio::class)->executarComo($alfa, fn () => expect(TutorModel::query()->count())->toBe(1));
});
it('isola a tabela de animais e a busca por nome de animal', function (): void {
    $alfa = Laboratorios::criar();
    $beta = Laboratorios::criar();
    actingAs(Laboratorios::usuarioEm($alfa))->post(route('lab.tutores.store'), tutorPayload());
    $animal = DB::table('animais')->value('id');
    app(ContextoLaboratorio::class)->executarComo($beta, function () use ($animal): void {
        expect(AnimalModel::query()->count())->toBe(0)->and(app(Vidra\Veterinaria\Domain\Animal\AnimalRepository::class)->porId(new Vidra\Veterinaria\Domain\Animal\AnimalId($animal)))->toBeNull();
    });
    actingAs(Laboratorios::usuarioEm($beta))->get(route('lab.tutores.index', ['busca' => 'Amora']))->assertInertia(fn ($p) => $p->has('tutores', 0));
});
it('valida contato CPF nascimento peso microchip e idade aproximada', function (string $campo, mixed $valor): void {
    $payload = tutorPayload();
    data_set($payload, $campo, $valor);
    actingAs(Laboratorios::usuario())->post(route('lab.tutores.store'), $payload)->assertSessionHasErrors($campo);
    $this->assertDatabaseCount('tutores', 0);
    $this->assertDatabaseCount('animais', 0);
})->with([['celular', '123'], ['email', 'invalido'], ['cpf', '111.111.111-11'], ['animais.0.peso', '2000'], ['animais.0.peso', '0'], ['animais.0.nascimento', '2099-01-01'], ['animais.0.microchip', '123'], ['animais.0.especie', 'invalida']]);
it('converte idade aproximada e permite adicionar animal e alterar contato', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.tutores.store'), tutorPayload());
    $id = DB::table('tutores')->value('id');
    $this->post(route('lab.tutores.animais.store', ['tutor' => $id]), animalPayload(['nome' => 'Bidu', 'modo_idade' => 'aproximada', 'anos' => '4', 'meses' => '2']))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('animais', ['nome' => 'Bidu', 'nascimento_estimado' => true]);
    app(ContextoLaboratorio::class)->executarComo(DB::table('tutores')->where('id', $id)->value('laboratorio_id'), function (): void {
        expect(AnimalModel::query()->where('nome', 'Bidu')->first()->nascimento->format('Y-m-d'))->toBe('2022-09-08');
    });
    $this->put(route('lab.tutores.update', ['tutor' => $id]), tutorPayload(['email' => 'novo@example.com', 'autoriza_email' => false]))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('tutores', ['email' => 'novo@example.com', 'email_autorizado_em' => null]);
});
it('recusa autorização sem e-mail e desfaz o cadastro', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.tutores.store'), tutorPayload(['email' => null]))->assertSessionHasErrors('email');
    $this->assertDatabaseCount('tutores', 0);
    $this->assertDatabaseCount('animais', 0);
});
it('desfaz tutor e primeiro animal se falhar ao salvar o segundo', function (): void {
    $repo = Mockery::mock(Vidra\Veterinaria\Domain\Animal\AnimalRepository::class);
    $repo->shouldReceive('nextIdentity')->twice()->andReturn(new Vidra\Veterinaria\Domain\Animal\AnimalId((string) Illuminate\Support\Str::uuid7()));
    $repo->shouldReceive('save')->once()->ordered()->andReturnUsing(fn ($a) => app(Vidra\Veterinaria\Infrastructure\Persistence\EloquentAnimalRepository::class)->save($a));
    $repo->shouldReceive('save')->once()->ordered()->andThrow(new RuntimeException('Falha no segundo animal'));
    $this->app->instance(Vidra\Veterinaria\Domain\Animal\AnimalRepository::class, $repo);
    $this->withoutExceptionHandling();
    try {
        actingAs(Laboratorios::usuario())->post(route('lab.tutores.store'), tutorPayload());
        test()->fail('Esperava falha');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('Falha no segundo animal');
    }
    $this->assertDatabaseCount('tutores', 0);
    $this->assertDatabaseCount('animais', 0);
});
it('mostra formulários com dados reais e pré-preenche celular', function (): void {
    actingAs(Laboratorios::usuario())->get(route('lab.tutores.create', ['celular' => '19997331208']))->assertOk()->assertInertia(fn ($p) => $p->component('lab/tutores/create')->where('celular', '19997331208')->has('perfis', 10)->has('vinculos', 0));
    $this->post(route('lab.tutores.store'), tutorPayload());
    $id = DB::table('tutores')->value('id');
    $this->get(route('lab.tutores.edit', ['tutor' => $id]))->assertOk();
    $this->get(route('lab.tutores.animais.create', ['tutor' => $id]))->assertOk();
});

it('lista vínculos locais e mostra o veterinário que acompanha o animal', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.veterinarios.store'), [
        'tratamento' => 'dra', 'nome' => 'Beatriz Nogueira', 'crmv_numero' => '55912', 'crmv_uf' => 'SP',
        'email' => 'beatriz@example.com', 'celular' => '19995108842', 'telefone_critico' => '1938692210',
        'quem_paga' => 'clinica', 'recebe_laudo' => 'cada_laudo', 've_pedidos_dos_colegas' => false,
    ])->assertSessionHasNoErrors();
    $vinculo = DB::table('laboratorio_veterinario')->value('id');
    $this->get(route('lab.tutores.create'))->assertInertia(fn ($p) => $p->has('vinculos', 1)->where('vinculos.0.id', $vinculo)->where('vinculos.0.nome', 'Dra. Beatriz Nogueira'));
    $this->post(route('lab.tutores.store'), tutorPayload(['animais' => [animalPayload(['vinculo_veterinario_id' => $vinculo])]]))->assertSessionHasNoErrors();
    $this->get(route('lab.tutores.index'))->assertInertia(fn ($p) => $p->where('selecionado.animais.0.veterinario', 'Dra. Beatriz Nogueira'));
    actingAs(Laboratorios::usuario())->post(route('lab.tutores.store'), tutorPayload(['animais' => [animalPayload(), animalPayload(['vinculo_veterinario_id' => $vinculo])]]))->assertSessionHasErrors('animais.1.vinculo_veterinario_id');
});

it('limita idade aproximada a quarenta anos e exige meses válidos', function (string $anos, string $meses, string $campo): void {
    actingAs(Laboratorios::usuario())->post(route('lab.tutores.store'), tutorPayload(['animais' => [animalPayload(['modo_idade' => 'aproximada', 'anos' => $anos, 'meses' => $meses])]]))->assertSessionHasErrors('animais.0.'.$campo);
    $this->assertDatabaseCount('tutores', 0);
})->with([['41', '0', 'anos'], ['40', '1', 'meses'], ['1', '12', 'meses'], ['-1', '0', 'anos']]);

it('aceita recém-nascido e quarenta anos na idade aproximada', function (string $anos): void {
    actingAs(Laboratorios::usuario())->post(route('lab.tutores.store'), tutorPayload(['animais' => [animalPayload(['modo_idade' => 'aproximada', 'anos' => $anos, 'meses' => '0'])]]))->assertSessionHasNoErrors();
})->with(['0', '40']);

it('limita o cadastro a um ou dois animais', function (int $quantidade): void {
    actingAs(Laboratorios::usuario())->post(route('lab.tutores.store'), tutorPayload(['animais' => array_fill(0, $quantidade, animalPayload())]))->assertSessionHasErrors('animais');
    $this->assertDatabaseCount('tutores', 0);
})->with([0, 3]);

it('mostra os cinquenta tutores mais recentes sem termo de busca', function (): void {
    $lab = Laboratorios::criar();
    $user = Laboratorios::usuarioEm($lab);
    app(ContextoLaboratorio::class)->executarComo($lab, function () use ($lab): void {
        for ($i = 0; $i < 51; $i++) {
            TutorModel::query()->create(['id' => (string) Illuminate\Support\Str::uuid7(), 'laboratorio_id' => $lab, 'nome' => 'Tutor '.$i, 'celular' => '119'.str_pad((string) $i, 8, '0', STR_PAD_LEFT), 'created_at' => now()->subMinutes(51 - $i)]);
        }
    });
    actingAs($user)->get(route('lab.tutores.index'))->assertInertia(fn ($p) => $p->has('tutores', 50)->where('tutores.0.nome', 'Tutor 50')->where('tutores.49.nome', 'Tutor 1'));
});

it('aceita idade com zero à esquerda, como o campo numérico do navegador manda', function (): void {
    actingAs(Laboratorios::usuario())
        ->post(route('lab.tutores.store'), tutorPayload(['animais' => [animalPayload(['modo_idade' => 'aproximada', 'anos' => '04', 'meses' => '00'])]]))
        ->assertSessionHasNoErrors();
});
