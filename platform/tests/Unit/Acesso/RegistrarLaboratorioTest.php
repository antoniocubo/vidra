<?php

declare(strict_types=1);

use Tests\Support\DadosCadastro;
use Tests\Support\InMemoryLaboratorioRepository;
use Vidra\Acesso\Application\RegistrarLaboratorio\RegistrarLaboratorio;
use Vidra\Acesso\Domain\CnpjJaCadastrado;
use Vidra\Acesso\Domain\EmailJaCadastrado;
use Vidra\Acesso\Domain\Relogio;

beforeEach(function (): void {
    $this->repository = new InMemoryLaboratorioRepository;
    $this->registrar = new RegistrarLaboratorio($this->repository, new class implements Relogio
    {
        public function agora(): DateTimeImmutable
        {
            return new DateTimeImmutable('2026-11-06T15:00:00Z');
        }
    });
});
it('registra o laboratório com administrador e devolve UUID v7', function (): void {
    $id = ($this->registrar)(DadosCadastro::input());
    expect($id->value[14])->toBe('7')->and($this->repository->items[$id->value]->testeTerminaEm->format('Y-m-d'))->toBe('2026-11-20')->and($this->repository->administradores[$id->value]->eRt)->toBeTrue();
});
it('não deixa repetir CNPJ normalizado', function (): void {
    ($this->registrar)(DadosCadastro::input());
    expect(fn () => ($this->registrar)(DadosCadastro::input('11222333000181', 'outro@lab.com.br')))->toThrow(CnpjJaCadastrado::class);
    expect($this->repository->items)->toHaveCount(1);
});
it('não deixa repetir e-mail normalizado', function (): void {
    ($this->registrar)(DadosCadastro::input());
    expect(fn () => ($this->registrar)(DadosCadastro::input('04252011000110', ' MARINA@ALFAVET.COM.BR ')))->toThrow(EmailJaCadastrado::class);
    expect($this->repository->items)->toHaveCount(1);
});
