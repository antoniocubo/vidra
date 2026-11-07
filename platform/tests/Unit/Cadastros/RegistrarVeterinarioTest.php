<?php

declare(strict_types=1);

use Tests\Support\InMemoryVeterinarioRepository;
use Vidra\Cadastros\Application\RegistrarVeterinario\RegistrarVeterinario;
use Vidra\Cadastros\Application\RegistrarVeterinario\RegistrarVeterinarioInput;
use Vidra\Cadastros\Domain\Veterinario\Exceptions\CrmvJaCadastrado;

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
    );
}

beforeEach(function (): void {
    $this->repo = new InMemoryVeterinarioRepository;
    $this->registrar = new RegistrarVeterinario($this->repo);
});

it('registra o veterinário com dados normalizados', function (): void {
    $id = ($this->registrar)(inputBeatriz());

    $vet = $this->repo->items[$id->value];
    expect($vet->nomeDeExibicao())->toBe('Dra. Beatriz Nogueira')
        ->and((string) $vet->crmv)->toBe('CRMV-SP 55912')
        ->and($vet->email()->value)->toBe('beatriz.nogueira@vetcare.com.br')
        ->and($vet->celular()->formatted())->toBe('(19) 99510-8842')
        ->and($vet->telefoneCritico()->formatted())->toBe('(19) 3869-2210');
});

it('não deixa repetir o CRMV na mesma UF', function (): void {
    ($this->registrar)(inputBeatriz());
    ($this->registrar)(inputBeatriz('055912'));
})->throws(CrmvJaCadastrado::class, 'O CRMV-SP 55912 já está cadastrado.');
