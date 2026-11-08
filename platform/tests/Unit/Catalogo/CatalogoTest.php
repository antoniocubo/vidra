<?php

declare(strict_types=1);
use Tests\Support\InMemoryExameRepository;
use Tests\Support\LaboratorioAtualFixo;
use Tests\Support\PerfisDeReferenciaFixos;
use Tests\Support\TransacaoEmMemoria;
use Vidra\Catalogo\Application\AlterarExame;
use Vidra\Catalogo\Application\CadastrarExame;
use Vidra\Catalogo\Application\DadosExameInput;
use Vidra\Catalogo\Application\DefinirFaixasDeReferencia;
use Vidra\Catalogo\Application\DefinirFaixasInput;
use Vidra\Catalogo\Application\DesativarExame;
use Vidra\Catalogo\Application\DetalharExame;
use Vidra\Catalogo\Application\ListarExames;
use Vidra\Catalogo\Application\MontarAnalitos;
use Vidra\Catalogo\Application\ObterExame;
use Vidra\Catalogo\Application\ReativarExame;
use Vidra\Catalogo\Domain\Exame\Analito;
use Vidra\Catalogo\Domain\Exame\CodigoDoExame;
use Vidra\Catalogo\Domain\Exame\Decimal;
use Vidra\Catalogo\Domain\Exame\Exceptions\AnalitoRepetido;
use Vidra\Catalogo\Domain\Exame\Exceptions\CodigoDeExameJaUsado;
use Vidra\Catalogo\Domain\Exame\Exceptions\FaixaInvalida;
use Vidra\Catalogo\Domain\Exame\Exceptions\PerfilDesconhecido;
use Vidra\Catalogo\Domain\Exame\FaixaDeReferencia;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;

it('normaliza o código do exame', function (): void {
    expect((new CodigoDoExame(' hemo-1 '))->value)->toBe('HEMO-1');
});
it('rejeita códigos fora do formato', function (string $codigo): void {
    expect(fn () => new CodigoDoExame($codigo))->toThrow(InvalidValue::class);
})->with(['A', 'ABCDEFGHIJKLM', 'HE MO', 'HE_MO', 'HÉMO', '']);
it('normaliza decimais sem perder precisão', function (string $entrada, string $esperado): void {
    expect((new Decimal($entrada))->value)->toBe($esperado);
})->with([['0001.2300', '1.23'], ['-000.0000', '0'], ['9999999999.9999', '9999999999.9999'], ['-0.0001', '-0.0001']]);
it('compara decimais com sinais e fronteiras', function (string $a, string $b, int $resultado): void {
    expect((new Decimal($a))->comparar(new Decimal($b)))->toBe($resultado);
})->with([['1', '1.0000', 0], ['9', '10', -1], ['-10', '-9', -1], ['-0.1', '0', -1], ['9999999999.9998', '9999999999.9999', -1], ['0.0001', '0', 1], ['1.2', '1.19', 1]]);
it('rejeita decimal impreciso ou fora da coluna', function (string $valor): void {
    expect(fn () => new Decimal($valor))->toThrow(InvalidValue::class);
})->with(['1,5', '1e2', 'NaN', '1.00001', '10000000000', '', '.2']);
it('aceita limites iguais e referências unilaterais', function (?string $min, ?string $max): void {
    $f = new FaixaDeReferencia('perfil', $min === null ? null : new Decimal($min), $max === null ? null : new Decimal($max));
    expect($f->perfil)->toBe('perfil');
})->with([['1', '1'], [null, '0'], ['-1', null]]);
it('rejeita todas as fronteiras inválidas de faixa', function (?string $min, ?string $max, ?string $baixo, ?string $alto, string $campo): void {
    $d = fn (?string $v): ?Decimal => $v === null ? null : new Decimal($v);
    try {
        new FaixaDeReferencia('perfil', $d($min), $d($max), $d($baixo), $d($alto));
        test()->fail('Esperava faixa inválida');
    } catch (FaixaInvalida $e) {
        expect($e->campo)->toBe($campo);
    }
})->with([[null, null, null, null, 'minimo'], ['2', '1', null, null, 'maximo'], ['1', '2', '1', null, 'criticoBaixo'], ['1', '2', '1.0001', null, 'criticoBaixo'], ['1', '2', null, '2', 'criticoAlto'], ['1', '2', null, '1.9999', 'criticoAlto']]);
it('aceita críticos estritamente externos e sem limite correspondente', function (): void {
    expect(new FaixaDeReferencia('perfil', new Decimal('1'), new Decimal('2'), new Decimal('0.9999'), new Decimal('2.0001')))->toBeInstanceOf(FaixaDeReferencia::class);
    expect(new FaixaDeReferencia('perfil', null, new Decimal('2'), new Decimal('10')))->toBeInstanceOf(FaixaDeReferencia::class);
    expect(new FaixaDeReferencia('perfil', new Decimal('2'), null, null, new Decimal('1')))->toBeInstanceOf(FaixaDeReferencia::class);
});
beforeEach(function (): void {
    $this->repo = new InMemoryExameRepository;
    $this->lab = new LaboratorioAtualFixo;
    $this->transacao = new TransacaoEmMemoria;
    $this->montar = new MontarAnalitos($this->repo);
    $this->obter = new ObterExame($this->repo, $this->lab);
    $this->input = new DadosExameInput('HEMO', 'Hemograma completo', 'hematologia', 'Sangue', null, 24, 1250, [['nome' => 'Hemácias', 'unidade' => '/µL', 'casas_decimais' => 2]]);
    $this->cadastrar = new CadastrarExame($this->repo, $this->lab, $this->transacao, $this->montar);
});
it('cadastra e impede código repetido', function (): void {
    $id = ($this->cadastrar)($this->input);
    expect($this->repo->porId($id)->laboratorioId)->toBe($this->lab->id());
    expect(fn () => ($this->cadastrar)($this->input))->toThrow(CodigoDeExameJaUsado::class, 'O código HEMO já é usado pelo exame Hemograma completo.');
});
it('exige analito e impede nomes repetidos', function (): void {
    $id = ($this->cadastrar)($this->input);
    $e = $this->repo->porId($id);
    expect(fn () => $e->definirAnalitos([]))->toThrow(InvalidValue::class);
    $a = $e->analitos[0];
    expect(fn () => $e->definirAnalitos([$a, new Analito($this->repo->nextAnalitoIdentity(), ' hemácias ', null, 0)]))->toThrow(AnalitoRepetido::class);
});
it('substitui faixas e preserva referências ao alterar e reordenar', function (): void {
    $id = ($this->cadastrar)($this->input);
    $a = $this->repo->porId($id)->analitos[0];
    $definir = new DefinirFaixasDeReferencia($this->repo, $this->obter, $this->transacao, new PerfisDeReferenciaFixos);
    $definir($id->value, new DefinirFaixasInput($a->id->value, [['perfil' => 'cao.adulto', 'minimo' => '1', 'maximo' => '2', 'criticoBaixo' => null, 'criticoAlto' => null]]));
    (new AlterarExame($this->repo, $this->obter, $this->transacao, $this->montar))($id->value, new DadosExameInput('HEMO', 'Hemograma', 'hematologia', 'Sangue', 'Manual', 48, 1500, [['id' => $a->id->value, 'nome' => 'Hemácias', 'unidade' => null, 'casas_decimais' => 3]]));
    expect($this->repo->porId($id)->analitos[0]->faixas)->toHaveCount(1);
    expect((new DetalharExame($this->obter, new PerfisDeReferenciaFixos))($id->value)['exame']->perfisCobertos)->toBe(1);
    $definir($id->value, new DefinirFaixasInput($a->id->value, []));
    expect($this->repo->porId($id)->analitos[0]->faixas)->toBe([]);
});
it('recusa perfil desconhecido sem gravar', function (): void {
    $id = ($this->cadastrar)($this->input);
    $a = $this->repo->porId($id)->analitos[0];
    $definir = new DefinirFaixasDeReferencia($this->repo, $this->obter, $this->transacao, new PerfisDeReferenciaFixos);
    expect(fn () => $definir($id->value, new DefinirFaixasInput($a->id->value, [['perfil' => 'inexistente', 'minimo' => '1', 'maximo' => null, 'criticoBaixo' => null, 'criticoAlto' => null]])))->toThrow(PerfilDesconhecido::class);
    expect($this->repo->porId($id)->analitos[0]->faixas)->toBe([]);
});
it('desativa reativa e filtra exames', function (): void {
    $id = ($this->cadastrar)($this->input);
    $listar = new ListarExames($this->repo, $this->lab);
    (new DesativarExame($this->repo, $this->obter, $this->transacao))($id->value);
    expect($listar())->toBe([])->and($listar('hemo', null, true))->toHaveCount(1);
    (new ReativarExame($this->repo, $this->obter, $this->transacao))($id->value);
    expect($listar('hemo', 'hematologia'))->toHaveCount(1)->and($listar('hemo', 'bioquimica'))->toBe([]);
});

it('valida prazo preço e casas decimais nas fronteiras', function (): void {
    $id = ($this->cadastrar)($this->input);
    $exame = $this->repo->porId($id);
    foreach ([0, 721] as $prazo) {
        expect(fn () => $exame->alterarDados($exame->codigo, $exame->nome, $exame->setor, $exame->material, null, $prazo, 0))->toThrow(InvalidValue::class);
    }
    expect(fn () => $exame->alterarDados($exame->codigo, $exame->nome, $exame->setor, $exame->material, null, 1, -1))->toThrow(InvalidValue::class);
    foreach ([1, 720] as $prazo) {
        $exame->alterarDados($exame->codigo, $exame->nome, $exame->setor, $exame->material, null, $prazo, 0);
        expect($exame->prazoHoras)->toBe($prazo);
    }
    foreach ([-1, 5] as $casas) {
        expect(fn () => new Analito($this->repo->nextAnalitoIdentity(), 'Resultado', null, $casas))->toThrow(InvalidValue::class);
    }
});

it('não repete perfis e define e remove faixas pelo agregado', function (): void {
    $id = ($this->cadastrar)($this->input);
    $exame = $this->repo->porId($id);
    $analito = $exame->analitos[0];
    $faixa = new FaixaDeReferencia('cao.adulto', new Decimal('1'), new Decimal('2'));
    expect(fn () => $exame->substituirFaixas($analito->id, [$faixa, $faixa]))->toThrow(FaixaInvalida::class);
    $exame->definirFaixa($analito->id, 'cao.adulto', new Decimal('1'), new Decimal('2'));
    $exame->definirFaixa($analito->id, 'cao.adulto', new Decimal('2'), new Decimal('3'));
    expect($exame->analitos[0]->faixas)->toHaveCount(1)->and($exame->analitos[0]->faixas[0]->minimo->value)->toBe('2');
    $exame->removerFaixa($analito->id, 'cao.adulto');
    expect($exame->analitos[0]->faixas)->toBe([]);
});
