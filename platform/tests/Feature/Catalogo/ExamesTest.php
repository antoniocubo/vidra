<?php

declare(strict_types=1);
use Illuminate\Support\Facades\DB;
use Tests\Support\Laboratorios;
use Tests\Support\PerfisDeReferenciaFixos;
use Vidra\Catalogo\Domain\Referencia\PerfisDeReferencia;
use Vidra\Catalogo\Infrastructure\Persistence\AnalitoModel;
use Vidra\Catalogo\Infrastructure\Persistence\ExameModel;
use Vidra\Catalogo\Infrastructure\Persistence\FaixaReferenciaModel;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;

use function Pest\Laravel\actingAs;

function dadosExameCatalogo(array $overrides = []): array
{
    return array_replace(['codigo' => 'hemo', 'nome' => 'Hemograma completo', 'setor' => 'hematologia', 'material' => 'Sangue total', 'metodo' => null, 'prazo_horas' => 24, 'preco_centavos' => 1250, 'analitos' => [['nome' => 'Hemácias', 'unidade' => '/µL', 'casas_decimais' => 2], ['nome' => 'Leucócitos', 'unidade' => '/µL', 'casas_decimais' => 0]]], $overrides);
}
function faixaExameCatalogo(array $overrides = []): array
{
    return array_replace(['perfil' => 'cao.adulto', 'minimo' => '0,5', 'maximo' => '1,5', 'criticoBaixo' => '0,2', 'criticoAlto' => '5,0'], $overrides);
}
beforeEach(function (): void {
    $this->app->instance(PerfisDeReferencia::class, new PerfisDeReferenciaFixos);
});
it('mostra cadastro lista detalhe edição e referências', function (): void {
    actingAs(Laboratorios::usuario())->get(route('lab.exames.create'))->assertOk()->assertInertia(fn ($p) => $p->component('lab/exames/create'));
    $this->post(route('lab.exames.store'), dadosExameCatalogo())->assertSessionHasNoErrors();
    $id = DB::table('exames')->value('id');
    $this->get(route('lab.exames.index', ['exame' => $id]))->assertOk()->assertInertia(fn ($p) => $p->component('lab/exames/index')->has('exames', 1)->where('detalhe.exame.contagemAnalitos', 2)->has('detalhe.perfis', 2));
    $this->get(route('lab.exames.edit', $id))->assertOk()->assertInertia(fn ($p) => $p->component('lab/exames/edit'));
    $this->get(route('lab.exames.referencias', $id))->assertOk()->assertInertia(fn ($p) => $p->component('lab/exames/referencias')->has('perfis', 2));
});
it('cadastra o agregado com UUID v7 ordem e laboratório', function (): void {
    $lab = Laboratorios::criar();
    actingAs(Laboratorios::usuarioEm($lab))->post(route('lab.exames.store'), dadosExameCatalogo())->assertSessionHasNoErrors();
    $this->assertDatabaseHas('exames', ['laboratorio_id' => $lab, 'codigo' => 'HEMO', 'preco_centavos' => 1250]);
    $this->assertDatabaseHas('analitos', ['laboratorio_id' => $lab, 'nome' => 'Leucócitos', 'ordem' => 1]);
    expect(substr(DB::table('exames')->value('id'), 14, 1))->toBe('7')->and(substr(DB::table('analitos')->value('id'), 14, 1))->toBe('7');
});
it('impede código repetido no mesmo laboratório mas permite em outro', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.exames.store'), dadosExameCatalogo())->assertSessionHasNoErrors();
    $this->post(route('lab.exames.store'), dadosExameCatalogo())->assertSessionHasErrors(['codigo' => 'O código HEMO já é usado pelo exame Hemograma completo.']);
    actingAs(Laboratorios::usuario())->post(route('lab.exames.store'), dadosExameCatalogo())->assertSessionHasNoErrors();
    $this->assertDatabaseCount('exames', 2);
});
it('define faixas com vírgula e remove linhas vazias', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.exames.store'), dadosExameCatalogo());
    $id = DB::table('exames')->value('id');
    $a = DB::table('analitos')->orderBy('ordem')->value('id');
    $this->put(route('lab.exames.referencias.update', $id), ['analitoId' => $a, 'faixas' => [faixaExameCatalogo()]])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('faixas_referencia', ['analito_id' => $a, 'perfil' => 'cao.adulto', 'minimo' => '0.5', 'maximo' => '1.5']);
    $this->get(route('lab.exames.index', ['exame' => $id]))->assertInertia(fn ($p) => $p->where('detalhe.exame.analitos.0.faixas.0.minimo', '0.5')->where('detalhe.exame.perfisCobertos', 1));
    $this->put(route('lab.exames.referencias.update', $id), ['analitoId' => $a, 'faixas' => [faixaExameCatalogo(['minimo' => '', 'maximo' => '', 'criticoBaixo' => '', 'criticoAlto' => ''])]])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('faixas_referencia', 0);
});
it('informa o erro na célula e não grava faixa inválida', function (array $patch, string $campo): void {
    actingAs(Laboratorios::usuario())->post(route('lab.exames.store'), dadosExameCatalogo());
    $id = DB::table('exames')->value('id');
    $a = DB::table('analitos')->value('id');
    $this->put(route('lab.exames.referencias.update', $id), ['analitoId' => $a, 'faixas' => [faixaExameCatalogo($patch)]])->assertSessionHasErrors('faixas.0.'.$campo);
    $this->assertDatabaseCount('faixas_referencia', 0);
})->with([[['perfil' => 'desconhecido'], 'perfil'], [['minimo' => '2'], 'maximo'], [['criticoBaixo' => '0,5'], 'criticoBaixo'], [['criticoAlto' => '1,5'], 'criticoAlto'], [['minimo' => '1,00001'], 'minimo'], [['minimo' => '', 'maximo' => ''], 'minimo']]);
it('reordena analitos preserva faixas e apaga os removidos', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.exames.store'), dadosExameCatalogo());
    $id = DB::table('exames')->value('id');
    $ids = DB::table('analitos')->orderBy('ordem')->pluck('id');
    $this->put(route('lab.exames.referencias.update', $id), ['analitoId' => $ids[0], 'faixas' => [faixaExameCatalogo()]])->assertSessionHasNoErrors();
    $analitos = dadosExameCatalogo()['analitos'];
    $analitos[0]['id'] = $ids[0];
    $analitos[1]['id'] = $ids[1];
    $this->put(route('lab.exames.update', $id), dadosExameCatalogo(['analitos' => array_reverse($analitos)]))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('analitos', ['id' => $ids[0], 'ordem' => 1]);
    $this->assertDatabaseCount('faixas_referencia', 1);
    $this->put(route('lab.exames.update', $id), dadosExameCatalogo(['analitos' => [$analitos[1]]]))->assertSessionHasNoErrors();
    $this->assertDatabaseCount('analitos', 1);
    $this->assertDatabaseCount('faixas_referencia', 0);
});
it('desativa reativa busca e filtra por setor', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.exames.store'), dadosExameCatalogo());
    $id = DB::table('exames')->value('id');
    $this->post(route('lab.exames.desativar', $id))->assertSessionHasNoErrors();
    $this->get(route('lab.exames.index'))->assertInertia(fn ($p) => $p->has('exames', 0));
    $this->get(route('lab.exames.index', ['inativos' => 1]))->assertInertia(fn ($p) => $p->has('exames', 1)->where('exames.0.ativo', false));
    $this->post(route('lab.exames.reativar', $id))->assertSessionHasNoErrors();
    foreach (['hemograma', 'hemo'] as $busca) {
        $this->get(route('lab.exames.index', ['busca' => $busca, 'setor' => 'hematologia']))->assertInertia(fn ($p) => $p->has('exames', 1));
    }
    $this->get(route('lab.exames.index', ['setor' => 'bioquimica']))->assertInertia(fn ($p) => $p->has('exames', 0));
});
it('devolve 404 em todas as URLs de exame de outro laboratório', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.exames.store'), dadosExameCatalogo());
    $id = DB::table('exames')->value('id');
    $a = DB::table('analitos')->value('id');
    actingAs(Laboratorios::usuario());
    $this->get(route('lab.exames.index', ['exame' => $id]))->assertNotFound();
    foreach (['edit', 'referencias'] as $acao) {
        $this->get(route('lab.exames.'.$acao, $id))->assertNotFound();
    }
    $this->put(route('lab.exames.update', $id), dadosExameCatalogo())->assertNotFound();
    $this->put(route('lab.exames.referencias.update', $id), ['analitoId' => $a, 'faixas' => []])->assertNotFound();
    foreach (['desativar', 'reativar'] as $acao) {
        $this->post(route('lab.exames.'.$acao, $id))->assertNotFound();
    }
});
it('isola os dados de cada tabela entre laboratórios', function (string $model): void {
    $alfa = Laboratorios::criar();
    $beta = Laboratorios::criar();
    actingAs(Laboratorios::usuarioEm($alfa))->post(route('lab.exames.store'), dadosExameCatalogo());
    $id = DB::table('exames')->value('id');
    $a = DB::table('analitos')->value('id');
    $this->put(route('lab.exames.referencias.update', $id), ['analitoId' => $a, 'faixas' => [faixaExameCatalogo()]])->assertSessionHasNoErrors();
    app(ContextoLaboratorio::class)->executarComo($beta, function () use ($model): void {
        expect($model::query()->count())->toBe(0);
    });
    app(ContextoLaboratorio::class)->executarComo($alfa, function () use ($model): void {
        expect($model::query()->count())->toBeGreaterThan(0);
    });
})->with([ExameModel::class, AnalitoModel::class, FaixaReferenciaModel::class]);
it('não aceita analito pertencente a outro exame', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.exames.store'), dadosExameCatalogo());
    $a = DB::table('analitos')->value('id');
    $this->post(route('lab.exames.store'), dadosExameCatalogo(['codigo' => 'CREA']));
    $id = DB::table('exames')->where('codigo', 'CREA')->value('id');
    $this->put(route('lab.exames.referencias.update', $id), ['analitoId' => $a, 'faixas' => [faixaExameCatalogo()]])->assertSessionHasErrors('form');
    $this->assertDatabaseCount('faixas_referencia', 0);
});

it('preserva todas as referências quando a segunda linha é inválida', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.exames.store'), dadosExameCatalogo());
    $id = DB::table('exames')->value('id');
    $analito = DB::table('analitos')->value('id');
    $this->put(route('lab.exames.referencias.update', $id), ['analitoId' => $analito, 'faixas' => [faixaExameCatalogo()]])->assertSessionHasNoErrors();
    $this->put(route('lab.exames.referencias.update', $id), ['analitoId' => $analito, 'faixas' => [faixaExameCatalogo(['minimo' => '0,6']), faixaExameCatalogo(['perfil' => 'gato.adulto', 'maximo' => '0,1'])]])->assertSessionHasErrors('faixas.1.maximo');
    $this->assertDatabaseHas('faixas_referencia', ['analito_id' => $analito, 'minimo' => '0.5']);
    $this->assertDatabaseCount('faixas_referencia', 1);
});

it('desfaz o exame quando a persistência de um analito falha', function (): void {
    actingAs(Laboratorios::usuario());
    AnalitoModel::creating(function (): void {
        throw new RuntimeException('Falha ao gravar analito');
    });
    $this->withoutExceptionHandling();
    try {
        $this->post(route('lab.exames.store'), dadosExameCatalogo());
        test()->fail('Esperava falha ao gravar');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('Falha ao gravar analito');
    } finally {
        AnalitoModel::flushEventListeners();
        AnalitoModel::clearBootedModels();
    }
    $this->assertDatabaseCount('exames', 0);
    $this->assertDatabaseCount('analitos', 0);
});

it('valida falta de analitos nomes repetidos e campos numéricos', function (array $patch, string $campo): void {
    actingAs(Laboratorios::usuario())->post(route('lab.exames.store'), dadosExameCatalogo($patch))->assertSessionHasErrors($campo);
    $this->assertDatabaseCount('exames', 0);
})->with([
    [['analitos' => []], 'analitos'],
    [['analitos' => [['nome' => 'Resultado', 'casas_decimais' => 0], ['nome' => 'resultado', 'casas_decimais' => 4]]], 'analitos.0.nome'],
    [['prazo_horas' => 721], 'prazo_horas'],
    [['preco_centavos' => -1], 'preco_centavos'],
]);
