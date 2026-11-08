<?php

declare(strict_types=1);

use Tests\Support\InMemoryClinicaRepository;
use Tests\Support\InMemoryVeterinarioRepository;
use Tests\Support\InMemoryVinculoRepository;
use Tests\Support\LaboratorioAtualFixo;
use Tests\Support\TransacaoEmMemoria;
use Vidra\Shared\Domain\ValueObjects\Email;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Shared\Domain\ValueObjects\Uf;
use Vidra\Veterinaria\Application\AlterarClinica\AlterarClinica;
use Vidra\Veterinaria\Application\AlterarVinculo\AlterarVinculo;
use Vidra\Veterinaria\Application\AlterarVinculo\AlterarVinculoInput;
use Vidra\Veterinaria\Application\CadastrarClinica\CadastrarClinica;
use Vidra\Veterinaria\Application\Clinica\DadosDaClinicaInput;
use Vidra\Veterinaria\Application\Clinica\MontarDadosDaClinica;
use Vidra\Veterinaria\Application\Clinica\ObterClinica;
use Vidra\Veterinaria\Application\DesativarClinica\DesativarClinica;
use Vidra\Veterinaria\Application\DetalharClinica\DetalharClinica;
use Vidra\Veterinaria\Application\DetalharVinculo\DetalharVinculo;
use Vidra\Veterinaria\Application\ListarClinicas\ListarClinicas;
use Vidra\Veterinaria\Application\ListarClinicas\ListarClinicasInput;
use Vidra\Veterinaria\Application\ListarVeterinarios\ListarVeterinarios;
use Vidra\Veterinaria\Application\ListarVeterinarios\MontarVeterinariosDto;
use Vidra\Veterinaria\Application\ReativarClinica\ReativarClinica;
use Vidra\Veterinaria\Domain\Clinica\Exceptions\ClinicaNaoEncontrada;
use Vidra\Veterinaria\Domain\Clinica\Exceptions\CnpjDeClinicaJaCadastrado;
use Vidra\Veterinaria\Domain\Clinica\Faturamento;
use Vidra\Veterinaria\Domain\Clinica\FormaDePagamento;
use Vidra\Veterinaria\Domain\Veterinario\Crmv;
use Vidra\Veterinaria\Domain\Veterinario\Tratamento;
use Vidra\Veterinaria\Domain\Veterinario\Veterinario;
use Vidra\Veterinaria\Domain\Vinculo\Exceptions\VinculoNaoEncontrado;
use Vidra\Veterinaria\Domain\Vinculo\QuemPaga;
use Vidra\Veterinaria\Domain\Vinculo\RecebeLaudo;
use Vidra\Veterinaria\Domain\Vinculo\Vinculo;

function inputClinica(string $nome = 'Clínica Patas', ?string $cnpj = '11.222.333/0001-81', string $cidade = 'Campinas', int $desconto = 10): DadosDaClinicaInput
{
    return new DadosDaClinicaInput(
        nomeFantasia: $nome,
        cidade: $cidade,
        emailDeFaturamento: 'Financeiro@ClinicaPatas.com.br',
        desconto: $desconto,
        diaDeFechamento: 31,
        diaDeVencimento: 10,
        formaDePagamento: 'pix',
        cnpj: $cnpj,
        bairro: 'Cambuí',
        telefoneCritico: '(19) 3241-0099',
    );
}

beforeEach(function (): void {
    $this->laboratorio = new LaboratorioAtualFixo;
    $this->clinicas = new InMemoryClinicaRepository($this->laboratorio);
    $this->veterinarios = new InMemoryVeterinarioRepository;
    $this->vinculos = new InMemoryVinculoRepository($this->laboratorio);
    $this->transacao = new TransacaoEmMemoria;
    $this->cadastrar = new CadastrarClinica($this->clinicas, $this->laboratorio, new MontarDadosDaClinica, $this->transacao);
    $this->obter = new ObterClinica($this->clinicas);
    $this->montar = new MontarVeterinariosDto($this->veterinarios, $this->clinicas);
});

function veterinarioComVinculo(object $teste, string $nome, string $crmv, array $clinicaIds = []): string
{
    $vet = Veterinario::registrar($teste->veterinarios->nextIdentity(), Tratamento::Dra, $nome, new Crmv($crmv, Uf::SP), new Email('vet'.$crmv.'@exemplo.com.br'), new Telefone('19995108842'), 'Clínica geral');
    $teste->veterinarios->save($vet);
    $vinculo = Vinculo::registrar($teste->vinculos->nextIdentity(), $teste->laboratorio->id(), $vet->id, QuemPaga::Clinica, new Telefone('1932410099'), false, RecebeLaudo::CadaLaudo, $clinicaIds);
    $teste->vinculos->save($vinculo);

    return $vinculo->id->value;
}

describe('faturamento da clínica', function (): void {
    it('aceita o desconto de 0 a 100 e recusa fora disso', function (int $desconto, bool $valido): void {
        $criar = fn () => new Faturamento(new Email('f@exemplo.com'), $desconto, 31, 10, FormaDePagamento::Pix);
        $valido ? expect($criar()->desconto)->toBe($desconto) : expect($criar)->toThrow(InvalidValue::class);
    })->with([[0, true], [10, true], [100, true], [-1, false], [101, false]]);

    it('aceita fechamento de 1 a 28 ou o último dia (31) e recusa 29 e 30', function (int $dia, bool $valido): void {
        $criar = fn () => new Faturamento(new Email('f@exemplo.com'), 0, $dia, 10, FormaDePagamento::Boleto);
        $valido ? expect($criar()->diaDeFechamento)->toBe($dia) : expect($criar)->toThrow(InvalidValue::class);
    })->with([[1, true], [28, true], [31, true], [0, false], [29, false], [30, false], [32, false]]);

    it('aceita vencimento de 1 a 28', function (int $dia, bool $valido): void {
        $criar = fn () => new Faturamento(new Email('f@exemplo.com'), 0, 31, $dia, FormaDePagamento::Transferencia);
        $valido ? expect($criar()->diaDeVencimento)->toBe($dia) : expect($criar)->toThrow(InvalidValue::class);
    })->with([[1, true], [28, true], [0, false], [29, false], [31, false]]);

    it('sabe quando fecha no último dia do mês', function (): void {
        expect((new Faturamento(new Email('f@exemplo.com'), 0, 31, 10, FormaDePagamento::Pix))->fechaNoUltimoDia())->toBeTrue()
            ->and((new Faturamento(new Email('f@exemplo.com'), 0, 15, 10, FormaDePagamento::Pix))->fechaNoUltimoDia())->toBeFalse();
    });
});

describe('cadastro de clínica', function (): void {
    it('cadastra a clínica ativa, com CNPJ e e-mails normalizados', function (): void {
        $id = ($this->cadastrar)(inputClinica());

        $clinica = $this->clinicas->items[$id->value];
        $dados = $clinica->dados();
        expect($clinica->ativa())->toBeTrue()
            ->and($clinica->laboratorioId)->toBe($this->laboratorio->id())
            ->and($dados->cnpj?->value)->toBe('11222333000181')
            ->and($dados->faturamento->email->value)->toBe('financeiro@clinicapatas.com.br')
            ->and($dados->telefoneCritico?->digits)->toBe('1932410099')
            ->and($dados->faturamento->desconto)->toBe(10);
    });

    it('guarda como vazios os campos opcionais em branco', function (): void {
        $id = ($this->cadastrar)(new DadosDaClinicaInput('Clínica Sem Papelada', 'Valinhos', 'f@exemplo.com', 0, 31, 10, 'boleto', razaoSocial: '  ', cnpj: '', bairro: ' ', telefoneCritico: ''));

        $dados = $this->clinicas->items[$id->value]->dados();
        expect($dados->razaoSocial)->toBeNull()->and($dados->cnpj)->toBeNull()->and($dados->bairro)->toBeNull()->and($dados->telefoneCritico)->toBeNull();
    });

    it('não deixa repetir o CNPJ no mesmo laboratório', function (): void {
        ($this->cadastrar)(inputClinica());
        ($this->cadastrar)(inputClinica('Outra Clínica', '11222333000181'));
    })->throws(CnpjDeClinicaJaCadastrado::class, 'Já existe uma clínica com este CNPJ: Clínica Patas.');

    it('deixa outro laboratório usar o mesmo CNPJ', function (): void {
        ($this->cadastrar)(inputClinica());
        $outro = new LaboratorioAtualFixo('019a5d70-0000-7000-8000-000000000002');
        $repositorio = new InMemoryClinicaRepository($outro);
        $repositorio->items = $this->clinicas->items;

        $id = (new CadastrarClinica($repositorio, $outro, new MontarDadosDaClinica, $this->transacao))(inputClinica());

        expect($repositorio->items[$id->value]->laboratorioId)->toBe($outro->id());
    });

    it('recusa dados inválidos', function (DadosDaClinicaInput $input): void {
        ($this->cadastrar)($input);
    })->with([
        'nome vazio' => fn () => inputClinica(' '),
        'CNPJ inválido' => fn () => inputClinica(cnpj: '11222333000180'),
        'cidade vazia' => fn () => inputClinica(cidade: ''),
        'desconto acima de 100' => fn () => inputClinica(desconto: 101),
    ])->throws(InvalidValue::class);
});

describe('alteração, desativação e reativação', function (): void {
    beforeEach(function (): void {
        $this->alterar = new AlterarClinica($this->clinicas, $this->obter, new MontarDadosDaClinica, $this->transacao);
        $this->id = ($this->cadastrar)(inputClinica())->value;
    });

    it('altera os dados e aceita manter o próprio CNPJ', function (): void {
        ($this->alterar)($this->id, inputClinica('Clínica Patas Centro', '11.222.333/0001-81', 'Valinhos', 15));

        $dados = $this->clinicas->items[$this->id]->dados();
        expect($dados->nomeFantasia)->toBe('Clínica Patas Centro')->and($dados->cidade)->toBe('Valinhos')->and($dados->faturamento->desconto)->toBe(15);
    });

    it('não deixa trocar para o CNPJ de outra clínica', function (): void {
        ($this->cadastrar)(inputClinica('Outra', '11444777000161'));
        ($this->alterar)($this->id, inputClinica(cnpj: '11444777000161'));
    })->throws(CnpjDeClinicaJaCadastrado::class);

    it('desativa e reativa', function (): void {
        (new DesativarClinica($this->clinicas, $this->obter, $this->transacao))($this->id);
        expect($this->clinicas->items[$this->id]->ativa())->toBeFalse();

        (new ReativarClinica($this->clinicas, $this->obter, $this->transacao))($this->id);
        expect($this->clinicas->items[$this->id]->ativa())->toBeTrue();
    });

    it('não enxerga a clínica de outro laboratório', function (): void {
        $outro = new LaboratorioAtualFixo('019a5d70-0000-7000-8000-000000000002');
        $repositorio = new InMemoryClinicaRepository($outro);
        $repositorio->items = $this->clinicas->items;

        (new DesativarClinica($repositorio, new ObterClinica($repositorio), $this->transacao))($this->id);
    })->throws(ClinicaNaoEncontrada::class);
});

describe('lista e detalhe de clínicas', function (): void {
    beforeEach(function (): void {
        ($this->cadastrar)(inputClinica('Hospital Vet Bicho Feliz', '11444777000161', 'Campinas'));
        ($this->cadastrar)(inputClinica('Clínica Amigo Fiel', null, 'Sumaré', 0));
        $inativa = ($this->cadastrar)(inputClinica('Antiga Clínica', null, 'Hortolândia'));
        (new DesativarClinica($this->clinicas, $this->obter, $this->transacao))($inativa->value);
        $this->listar = new ListarClinicas($this->clinicas);
    });

    it('lista só as ativas, por nome', function (): void {
        expect(array_map(fn ($c) => $c->nomeFantasia, ($this->listar)()))->toBe(['Clínica Amigo Fiel', 'Hospital Vet Bicho Feliz']);
    });

    it('inclui as inativas quando pedido', function (): void {
        $todas = ($this->listar)(new ListarClinicasInput(incluirInativas: true));

        expect($todas)->toHaveCount(3)->and(array_column(array_map(fn ($c) => (array) $c, $todas), 'ativa'))->toContain(false);
    });

    it('busca por nome, CNPJ ou cidade sem diferenciar acento nem caixa', function (string $busca, string $esperada): void {
        $achadas = ($this->listar)(new ListarClinicasInput($busca, true));

        expect($achadas)->toHaveCount(1)->and($achadas[0]->nomeFantasia)->toBe($esperada);
    })->with([
        ['bicho', 'Hospital Vet Bicho Feliz'],
        ['11.444.777', 'Hospital Vet Bicho Feliz'],
        ['sumare', 'Clínica Amigo Fiel'],
        ['HORTOLANDIA', 'Antiga Clínica'],
    ]);

    it('detalha a clínica com os veterinários que atendem nela', function (): void {
        $clinica = ($this->listar)()[1];
        veterinarioComVinculo($this, 'Beatriz Nogueira', '55912', [$clinica->id]);
        veterinarioComVinculo($this, 'Ana Ribeiro', '38877', [$clinica->id]);
        veterinarioComVinculo($this, 'Paulo Viana', '60415');

        $dto = (new DetalharClinica($this->obter, $this->vinculos, $this->veterinarios))($clinica->id);

        expect(array_map(fn ($v) => $v->nome, $dto->veterinarios))->toBe(['Dra. Ana Ribeiro', 'Dra. Beatriz Nogueira'])
            ->and($dto->cnpj)->toBe('11.444.777/0001-61');
    });
});

describe('vínculo do veterinário', function (): void {
    beforeEach(function (): void {
        $this->patas = ($this->cadastrar)(inputClinica('Clínica Patas', '11222333000181'))->value;
        $this->amigo = ($this->cadastrar)(inputClinica('Clínica Amigo Fiel', '11444777000161'))->value;
        $this->vinculoId = veterinarioComVinculo($this, 'Beatriz Nogueira', '55912', [$this->patas]);
        $this->alterarVinculo = new AlterarVinculo($this->vinculos, $this->clinicas, $this->transacao);
    });

    it('altera as preferências e as clínicas sem mexer na pessoa', function (): void {
        ($this->alterarVinculo)(new AlterarVinculoInput($this->vinculoId, '(19) 98877-1203', 'tutor', 'resumo_diario', true, [$this->amigo]));

        $vinculo = $this->vinculos->items[$this->vinculoId];
        expect($vinculo->quemPaga)->toBe(QuemPaga::Tutor)
            ->and($vinculo->telefoneCritico->digits)->toBe('19988771203')
            ->and($vinculo->recebeLaudo)->toBe(RecebeLaudo::ResumoDiario)
            ->and($vinculo->vePedidosDosColegas)->toBeTrue()
            ->and($vinculo->clinicaIds)->toBe([$this->amigo])
            ->and($this->veterinarios->items)->toHaveCount(1);
    });

    it('recusa clínica que não é do laboratório', function (): void {
        ($this->alterarVinculo)(new AlterarVinculoInput($this->vinculoId, '19988771203', 'clinica', 'cada_laudo', false, ['019a5d70-0000-7000-8000-0000000000ff']));
    })->throws(ClinicaNaoEncontrada::class);

    it('não encontra vínculo de outro laboratório', function (): void {
        $outro = new LaboratorioAtualFixo('019a5d70-0000-7000-8000-000000000002');
        $vinculos = new InMemoryVinculoRepository($outro);
        $vinculos->items = $this->vinculos->items;

        (new AlterarVinculo($vinculos, $this->clinicas, $this->transacao))(new AlterarVinculoInput($this->vinculoId, '19988771203', 'clinica', 'cada_laudo', false));
    })->throws(VinculoNaoEncontrado::class);

    it('detalha o veterinário com as clínicas onde atende', function (): void {
        $dto = (new DetalharVinculo($this->vinculos, $this->montar))($this->vinculoId);

        expect($dto->nome)->toBe('Dra. Beatriz Nogueira')
            ->and($dto->crmv)->toBe('CRMV-SP 55912')
            ->and($dto->telefoneCritico)->toBe('(19) 3241-0099')
            ->and(array_map(fn ($c) => $c->nome, $dto->clinicas))->toBe(['Clínica Patas']);
    });

    it('lista por nome e busca por nome, CRMV ou clínica', function (): void {
        veterinarioComVinculo($this, 'Ana Ribeiro', '38877', [$this->amigo]);
        $listar = new ListarVeterinarios($this->vinculos, $this->montar);

        expect(array_map(fn ($v) => $v->nomeCompleto, $listar()))->toBe(['Ana Ribeiro', 'Beatriz Nogueira'])
            ->and(array_map(fn ($v) => $v->nomeCompleto, $listar('beatriz')))->toBe(['Beatriz Nogueira'])
            ->and(array_map(fn ($v) => $v->nomeCompleto, $listar('38877')))->toBe(['Ana Ribeiro'])
            ->and(array_map(fn ($v) => $v->nomeCompleto, $listar('amigo')))->toBe(['Ana Ribeiro'])
            ->and($listar('inexistente'))->toBe([]);
    });
});
