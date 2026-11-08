<?php

declare(strict_types=1);

use Tests\Support\InMemoryVeterinarioRepository;
use Tests\Support\InMemoryVinculoRepository;
use Tests\Support\LaboratorioAtualFixo;
use Tests\Support\TransacaoEmMemoria;
use Vidra\Veterinaria\Application\RegistrarVeterinario\RegistrarVeterinario;
use Vidra\Veterinaria\Application\RegistrarVeterinario\RegistrarVeterinarioInput;
use Vidra\Veterinaria\Domain\Vinculo\Exceptions\VeterinarioJaVinculado;

function inputBeatriz(string $crmv = '55912'): RegistrarVeterinarioInput
{
    return new RegistrarVeterinarioInput(
        tratamento: 'dra',
        nome: '  Beatriz   Nogueira ',
        crmvNumero: $crmv,
        crmvUf: 'SP',
        email: 'Beatriz.Nogueira@VetCare.com.br',
        celular: '(19) 99510-8842',
        telefoneCritico: '(19) 3869-2210',
        especialidade: 'Endocrinologia',
        quemPaga: 'clinica',
        recebeLaudo: 'cada_laudo',
        vePedidosDosColegas: false,
    );
}

beforeEach(function (): void {
    $this->repo = new InMemoryVeterinarioRepository;
    $this->laboratorio = new LaboratorioAtualFixo;
    $this->vinculos = new InMemoryVinculoRepository($this->laboratorio);
    $this->registrar = new RegistrarVeterinario($this->repo, $this->laboratorio, $this->vinculos, new TransacaoEmMemoria);
});

it('registra o veterinário com dados normalizados', function (): void {
    $id = ($this->registrar)(inputBeatriz());

    $vet = $this->repo->items[$id->id->value];
    expect($vet->nomeDeExibicao())->toBe('Dra. Beatriz Nogueira')
        ->and((string) $vet->crmv)->toBe('CRMV-SP 55912')
        ->and($vet->email()->value)->toBe('beatriz.nogueira@vetcare.com.br')
        ->and($vet->celular()->formatted())->toBe('(19) 99510-8842')
        ->and($id->reaproveitado)->toBeFalse();
    $vinculo = array_values($this->vinculos->items)[0];
    expect($vinculo->telefoneCritico->formatted())->toBe('(19) 3869-2210')
        ->and($vinculo->laboratorioId)->toBe($this->laboratorio->id())
        ->and($vinculo->quemPaga->value)->toBe('clinica');
});

it('não deixa repetir o vínculo no mesmo laboratório', function (): void {
    ($this->registrar)(inputBeatriz());
    ($this->registrar)(inputBeatriz('055912'));
})->throws(VeterinarioJaVinculado::class, 'Este veterinário já está cadastrado no seu laboratório.');

it('reaproveita o CRMV de outro laboratório sem alterar os dados globais', function (): void {
    $primeiro = ($this->registrar)(inputBeatriz());
    $original = $this->repo->items[$primeiro->id->value];
    $outroLaboratorio = new LaboratorioAtualFixo('019a5d70-0000-7000-8000-000000000002');
    $outrosVinculos = new InMemoryVinculoRepository($outroLaboratorio);
    $registrar = new RegistrarVeterinario($this->repo, $outroLaboratorio, $outrosVinculos, new TransacaoEmMemoria);
    $resultado = $registrar(new RegistrarVeterinarioInput('dr', 'Outro Nome', '055912', 'SP', 'outro@exemplo.com', '11999998888', '11988887777', 'tutor', 'resumo_diario', true));
    expect($resultado->reaproveitado)->toBeTrue()
        ->and($resultado->id)->toEqual($primeiro->id)
        ->and($resultado->nomeDeExibicao)->toBe('Dra. Beatriz Nogueira')
        ->and($this->repo->items)->toHaveCount(1)
        ->and($this->repo->items[$primeiro->id->value])->toBe($original)
        ->and($outrosVinculos->items)->toHaveCount(1);
    $vinculo = array_values($outrosVinculos->items)[0];
    expect($vinculo->telefoneCritico->digits)->toBe('11988887777')
        ->and($vinculo->quemPaga->value)->toBe('tutor')
        ->and($vinculo->recebeLaudo->value)->toBe('resumo_diario')
        ->and($vinculo->vePedidosDosColegas)->toBeTrue();
});
