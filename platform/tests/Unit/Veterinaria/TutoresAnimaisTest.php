<?php

declare(strict_types=1);
use Tests\Support\InMemoryAnimalRepository;
use Tests\Support\InMemoryTutorRepository;
use Tests\Support\LaboratorioAtualFixo;
use Tests\Support\TransacaoEmMemoria;
use Tests\Support\TutorRelogioFixo;
use Vidra\Shared\Domain\ValueObjects\Email;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Veterinaria\Application\AdicionarAnimal\AdicionarAnimal;
use Vidra\Veterinaria\Application\AdicionarAnimal\AdicionarAnimalInput;
use Vidra\Veterinaria\Application\AlterarTutor\AlterarTutor;
use Vidra\Veterinaria\Application\AlterarTutor\AlterarTutorInput;
use Vidra\Veterinaria\Application\CadastrarTutor\CadastrarTutor;
use Vidra\Veterinaria\Application\CadastrarTutor\CadastrarTutorInput;
use Vidra\Veterinaria\Application\RetirarAutorizacaoDeEmail\RetirarAutorizacaoDeEmail;
use Vidra\Veterinaria\Application\RetirarAutorizacaoDeEmail\RetirarAutorizacaoDeEmailInput;
use Vidra\Veterinaria\Domain\Animal\Especie;
use Vidra\Veterinaria\Domain\Referencia\FaixaEtaria;
use Vidra\Veterinaria\Domain\Tutor\Cpf;
use Vidra\Veterinaria\Domain\Tutor\Exceptions\CelularJaCadastrado;
use Vidra\Veterinaria\Domain\Tutor\Exceptions\TutorSemEmail;
use Vidra\Veterinaria\Domain\Tutor\Tutor;
use Vidra\Veterinaria\Domain\Tutor\TutorId;
use Vidra\Veterinaria\Domain\Vinculo\VinculosParaEscolha;
use Vidra\Veterinaria\Infrastructure\Referencia\PerfisVeterinarios;

it('normaliza o CPF com e sem máscara', function (): void {
    expect((new Cpf('529.982.247-25'))->digits)->toBe('52998224725')->and((new Cpf('52998224725'))->digits)->toBe('52998224725');
});
it('recusa CPF inválido', function (string $cpf): void {
    new Cpf($cpf);
})->with(['11111111111', '00000000000', '52998224724', '123', '529982247255'])->throws(InvalidValue::class);
it('calcula a faixa pela idade completa nas fronteiras', function (string $especie, string $idade, string $perfil): void {
    $hoje = new DateTimeImmutable('2026-11-08');
    expect(FaixaEtaria::perfil(Especie::from($especie), $hoje->modify($idade), $hoje))->toBe($perfil);
})->with([['cao', '-11 months', 'cao.filhote'], ['cao', '-1 year', 'cao.adulto'], ['cao', '-6 years -11 months', 'cao.adulto'], ['cao', '-7 years', 'cao.idoso'], ['gato', '-9 years -11 months', 'gato.adulto'], ['gato', '-10 years', 'gato.idoso'], ['equino', '-23 months', 'equino.potro'], ['equino', '-2 years', 'equino.adulto'], ['equino', '-15 years', 'equino.idoso'], ['ave', '-1 year', 'ave']]);
it('publica os dez perfis na ordem e não oferece perfil para outra espécie', function (): void {
    $perfis = new PerfisVeterinarios;
    expect(array_column($perfis->todos(), 'chave'))->toBe(['cao.filhote', 'cao.adulto', 'cao.idoso', 'gato.filhote', 'gato.adulto', 'gato.idoso', 'equino.potro', 'equino.adulto', 'equino.idoso', 'ave']);
    expect($perfis->existe('outra'))->toBeFalse()->and(FaixaEtaria::perfil(Especie::Outra, new DateTimeImmutable('2020-01-01'), new DateTimeImmutable('2026-01-01')))->toBeNull();
});
it('exige e-mail para autorizar laudos', function (): void {
    $t = Tutor::registrar(new TutorId('019a5d70-0000-7000-8000-000000000001'), 'lab', 'Ana Silva', new Telefone('11999998888'));
    $t->autorizarLaudoPorEmail(10, new DateTimeImmutable);
})->throws(TutorSemEmail::class);
it('guarda consentimento em UTC e o retira quando o endereço muda', function (): void {
    $t = Tutor::registrar(new TutorId('019a5d70-0000-7000-8000-000000000001'), 'lab', 'Ana Silva', new Telefone('11999998888'), new Email('ana@example.com'));
    $t->autorizarLaudoPorEmail(10, new DateTimeImmutable('2026-11-08 14:32:00 -03:00'));
    expect($t->autorizacao()->registradaEm->format('Y-m-d H:i:s T'))->toBe('2026-11-08 17:32:00 UTC');
    $t->alterarContato('Ana Silva', new Telefone('11999998888'), new Email('nova@example.com'), null, null);
    expect($t->autorizacao())->toBeNull();
});
beforeEach(function (): void {
    $this->tutores = new InMemoryTutorRepository;
    $this->animais = new InMemoryAnimalRepository;
    $this->relogio = new TutorRelogioFixo;
    $this->vinculos = new class implements VinculosParaEscolha
    {
        public function nomes(): array
        {
            return [];
        }
    };
    $this->adicionar = new AdicionarAnimal($this->animais, $this->tutores, new LaboratorioAtualFixo, $this->vinculos, $this->relogio);
    $this->cadastrar = new CadastrarTutor($this->tutores, new LaboratorioAtualFixo, new TransacaoEmMemoria, $this->relogio, $this->adicionar);
    $this->animal = ['nome' => 'Amora', 'especie' => 'cao', 'raca' => null, 'sexo' => 'femea', 'nascimento' => '2026-06-08', 'nascimentoEstimado' => true, 'pesoGramas' => 12400, 'castrado' => 'sim', 'microchip' => null, 'vinculoVeterinarioId' => null];
});
it('cadastra tutor com dois animais e autorização usando repositórios em memória', function (): void {
    $id = ($this->cadastrar)(new CadastrarTutorInput('Ana Silva', '11999998888', 'ana@example.com', null, null, true, 42, [$this->animal, $this->animal]));
    expect($this->animais->doTutor($id))->toHaveCount(2)->and($this->tutores->porId($id)->autorizacao()->registradaPor)->toBe(42);
    $a = $this->animais->doTutor($id)[0];
    expect($a->descricao($this->relogio->agora()))->toBe('Cão · fêmea · ~5 meses · 12,4 kg · castrada')->and($a->perfilDeReferencia($this->relogio->agora()))->toBe('cao.filhote');
});
it('recusa celular repetido no caso de uso', function (): void {
    $input = new CadastrarTutorInput('Ana Silva', '11999998888', null, null, null, false, 42, [$this->animal]);
    ($this->cadastrar)($input);
    ($this->cadastrar)($input);
})->throws(CelularJaCadastrado::class, 'Já existe um tutor com este celular: Ana Silva.');
it('adiciona animal e permite alterar contato e retirar autorização', function (): void {
    $id = ($this->cadastrar)(new CadastrarTutorInput('Ana Silva', '11999998888', null, null, null, false, 42, [$this->animal]));
    ($this->adicionar)(new AdicionarAnimalInput($id->value, $this->animal));
    $alterar = new AlterarTutor($this->tutores, $this->relogio, new TransacaoEmMemoria);
    $alterar(new AlterarTutorInput($id->value, 'Ana Souza', '11999998888', 'ana@example.com', null, null, true, 42));
    expect($this->animais->doTutor($id))->toHaveCount(2)->and($this->tutores->porId($id)->nome())->toBe('Ana Souza');
    (new RetirarAutorizacaoDeEmail($this->tutores))(new RetirarAutorizacaoDeEmailInput($id->value, 42));
    expect($this->tutores->porId($id)->autorizacao())->toBeNull();
});
