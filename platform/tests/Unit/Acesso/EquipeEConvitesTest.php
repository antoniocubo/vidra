<?php

declare(strict_types=1);

use Tests\Support\InMemoryContasDeUsuario;
use Tests\Support\InMemoryConviteRepository;
use Tests\Support\InMemoryEquipe;
use Tests\Support\InMemoryPortasDeConvite;
use Tests\Support\LaboratorioAtualFixo;
use Vidra\Acesso\Application\AceitarConvite;
use Vidra\Acesso\Application\AceitarConviteInput;
use Vidra\Acesso\Application\CancelarConvite;
use Vidra\Acesso\Application\ConsultarConvite;
use Vidra\Acesso\Application\ConvidarPessoa;
use Vidra\Acesso\Application\ConvidarPessoaInput;
use Vidra\Acesso\Application\ListarEquipe;
use Vidra\Acesso\Application\ReenviarConvite;
use Vidra\Acesso\Application\RemoverDaEquipe;
use Vidra\Acesso\Application\TrocarPapel;
use Vidra\Acesso\Domain\ConviteInvalido;
use Vidra\Acesso\Domain\JaFazParteDaEquipe;
use Vidra\Acesso\Domain\LaboratorioSemAdministrador;
use Vidra\Acesso\Domain\MembroDaEquipe;
use Vidra\Acesso\Domain\NaoPodeAlterarASiMesmo;
use Vidra\Acesso\Domain\Papel;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;

beforeEach(function (): void {
    $this->repo = new InMemoryConviteRepository;
    $this->equipe = new InMemoryEquipe;
    $this->contas = new InMemoryContasDeUsuario;
    $this->portas = new InMemoryPortasDeConvite;
    $this->lab = new LaboratorioAtualFixo('alfa');
    $this->equipe->dados['alfa'][1] = new MembroDaEquipe(1, 'Marina', 'marina@example.com', Papel::Admin, true);
    $this->convidar = new ConvidarPessoa($this->repo, $this->equipe, $this->portas, $this->portas, $this->portas, $this->portas, $this->lab, Tests\Support\AuditoriaFixa::registrar());
    $this->aceitar = new AceitarConvite($this->repo, $this->equipe, $this->contas, $this->portas, $this->portas, Tests\Support\AuditoriaFixa::registrar());
    $this->id = ($this->convidar)(new ConvidarPessoaInput('Ana Souza', 'ana@example.com', 'rt', 1));
    $this->token = $this->portas->envios[0]['token'];
});

it('convida substituindo o pendente com o papel atualizado', function (): void {
    $novo = ($this->convidar)(new ConvidarPessoaInput('Ana Souza', 'ANA@example.com', 'bancada', 1));
    expect($this->repo->findById('alfa', $this->id)->canceladoEm)->not->toBeNull()
        ->and($this->repo->pendentes('alfa'))->toHaveCount(1)
        ->and($this->repo->findById('alfa', $novo)->papel)->toBe(Papel::Bancada)
        ->and($this->portas->envios)->toHaveCount(2);
});

it('rejeita convite para membro da equipe', function (): void {
    expect(fn () => ($this->convidar)(new ConvidarPessoaInput('Marina', 'marina@example.com', 'rt', 1)))->toThrow(JaFazParteDaEquipe::class);
});

it('aceita criando conta pela porta e marcando uso único', function (): void {
    $resultado = ($this->aceitar)(new AceitarConviteInput($this->token, 'Ana Souza', 'senha-segura-123', null, null));
    expect($resultado->laboratorioId)->toBe('alfa')->and($this->contas->existe('ana@example.com'))->toBeTrue()
        ->and($this->equipe->dados['alfa'][$resultado->usuarioId]->eRt)->toBeTrue()
        ->and($this->repo->findById('alfa', $this->id)->aceitoEm)->toEqual($this->portas->data);
    expect(fn () => ($this->aceitar)(new AceitarConviteInput($this->token, '', '', $resultado->usuarioId, 'ana@example.com')))->toThrow(ConviteInvalido::class);
});

it('aceita conta existente apenas com identidade autenticada correspondente', function (): void {
    $this->contas->dados['ana@example.com'] = 5;
    expect(fn () => ($this->aceitar)(new AceitarConviteInput($this->token, 'Ana Souza', 'senha-segura-123', null, null)))->toThrow(InvalidValue::class);
    expect(fn () => ($this->aceitar)(new AceitarConviteInput($this->token, '', '', 1, 'marina@example.com')))->toThrow(InvalidValue::class);
    $r = ($this->aceitar)(new AceitarConviteInput($this->token, '', '', 5, 'ANA@example.com'));
    expect($r->usuarioId)->toBe(5)->and($this->contas->dados)->toHaveCount(1);
});

it('não aceita token inválido nem convite para quem já ganhou acesso', function (): void {
    expect(fn () => ($this->aceitar)(new AceitarConviteInput('errado', 'Ana Souza', 'senha-segura-123', null, null)))->toThrow(ConviteInvalido::class);
    $this->equipe->adicionar('alfa', 5, Papel::Rt);
    expect(fn () => ($this->aceitar)(new AceitarConviteInput($this->token, '', '', 5, 'ana@example.com')))->toThrow(JaFazParteDaEquipe::class);
});

it('não cria conta sem nome e senha válidos', function (): void {
    expect(fn () => ($this->aceitar)(new AceitarConviteInput($this->token, 'A', 'curta', null, null)))->toThrow(InvalidValue::class);
    expect($this->contas->dados)->toBe([])->and($this->repo->findById('alfa', $this->id)->aceitoEm)->toBeNull();
});

it('reenvia convite expirado invalidando o token antigo', function (): void {
    $this->portas->data = $this->portas->data->modify('+8 days');
    $reenviar = new ReenviarConvite($this->repo, $this->equipe, $this->portas, $this->portas, $this->portas, $this->portas, $this->lab);
    $reenviar($this->id);
    $novo = $this->repo->pendentes('alfa')[0];
    expect($novo->tokenHash)->not->toBe(hash('sha256', $this->token))->and($novo->expiraEm)->toEqual($this->portas->data->modify('+7 days'));
    expect(fn () => ($this->aceitar)(new AceitarConviteInput($this->token, 'Ana Souza', 'senha-segura-123', null, null)))->toThrow(ConviteInvalido::class);
});

it('cancela convite sem permitir novo aceite ou reenvio', function (): void {
    $cancelar = new CancelarConvite($this->repo, $this->portas, $this->portas, $this->lab);
    $cancelar($this->id);
    expect($this->repo->pendentes('alfa'))->toBe([]);
    expect(fn () => ($this->aceitar)(new AceitarConviteInput($this->token, 'Ana Souza', 'senha-segura-123', null, null)))->toThrow(ConviteInvalido::class);
    $reenviar = new ReenviarConvite($this->repo, $this->equipe, $this->portas, $this->portas, $this->portas, $this->portas, $this->lab);
    expect(fn () => $reenviar($this->id))->toThrow(ConviteInvalido::class);
});

it('lista equipe e convites apenas do laboratório atual sem expor hash', function (): void {
    $this->equipe->adicionar('beta', 99, Papel::Admin);
    $listar = new ListarEquipe($this->equipe, $this->repo, $this->lab);
    $dados = $listar();
    expect($dados['membros'])->toHaveCount(1)->and($dados['convites'])->toHaveCount(1)
        ->and($dados['convites'][0])->not->toHaveKey('tokenHash');
    $consultar = new ConsultarConvite($this->repo, $this->equipe, $this->contas, $this->portas);
    expect($consultar($this->token))->toMatchArray(['laboratorio' => 'Laboratório Alfa', 'temConta' => false]);
});

it('não permite gerir convite de outro laboratório', function (): void {
    $beta = new LaboratorioAtualFixo('beta');
    expect(fn () => (new CancelarConvite($this->repo, $this->portas, $this->portas, $beta))($this->id))->toThrow(ConviteInvalido::class);
    expect(fn () => (new ReenviarConvite($this->repo, $this->equipe, $this->portas, $this->portas, $this->portas, $this->portas, $beta))($this->id))->toThrow(ConviteInvalido::class);
});

it('preserva o último administrador ao remover ou rebaixar', function (): void {
    expect(fn () => (new RemoverDaEquipe($this->equipe, $this->portas, $this->lab, Tests\Support\AuditoriaFixa::registrar()))(2, 1))->toThrow(LaboratorioSemAdministrador::class);
    expect(fn () => (new TrocarPapel($this->equipe, $this->portas, $this->lab, Tests\Support\AuditoriaFixa::registrar()))(2, 1, 'rt'))->toThrow(LaboratorioSemAdministrador::class);
});

it('não permite alterar a própria participação', function (): void {
    $this->equipe->adicionar('alfa', 2, Papel::Admin);
    expect(fn () => (new RemoverDaEquipe($this->equipe, $this->portas, $this->lab, Tests\Support\AuditoriaFixa::registrar()))(1, 1))->toThrow(NaoPodeAlterarASiMesmo::class);
    expect(fn () => (new TrocarPapel($this->equipe, $this->portas, $this->lab, Tests\Support\AuditoriaFixa::registrar()))(1, 1, 'rt'))->toThrow(NaoPodeAlterarASiMesmo::class);
});

it('atualiza responsabilidade técnica de acordo com o papel e remove apenas o vínculo', function (): void {
    $this->equipe->adicionar('alfa', 2, Papel::Rt);
    $trocar = new TrocarPapel($this->equipe, $this->portas, $this->lab, Tests\Support\AuditoriaFixa::registrar());
    $trocar(1, 2, 'admin');
    expect($this->equipe->dados['alfa'][2]->eRt)->toBeTrue();
    $trocar(1, 2, 'bancada');
    expect($this->equipe->dados['alfa'][2]->eRt)->toBeFalse();
    (new RemoverDaEquipe($this->equipe, $this->portas, $this->lab, Tests\Support\AuditoriaFixa::registrar()))(1, 2);
    expect($this->equipe->listar('alfa'))->toHaveCount(1);
});
