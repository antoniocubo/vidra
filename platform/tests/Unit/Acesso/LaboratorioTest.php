<?php

declare(strict_types=1);

use Vidra\Acesso\Domain\Cnpj;
use Vidra\Acesso\Domain\Cobranca;
use Vidra\Acesso\Domain\Endereco;
use Vidra\Acesso\Domain\Laboratorio;
use Vidra\Acesso\Domain\LaboratorioId;
use Vidra\Acesso\Domain\LinhaDeProduto;
use Vidra\Acesso\Domain\Plano;
use Vidra\Acesso\Domain\RegistroProfissional;
use Vidra\Acesso\Domain\ResponsavelTecnico;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Shared\Domain\ValueObjects\Uf;

function criarLaboratorio(LinhaDeProduto $linha = LinhaDeProduto::Pet): Laboratorio
{
    return Laboratorio::registrar(new LaboratorioId('019a1234-5678-7000-8000-123456789abc'), ' Laboratório Alfa Vet ', new Cnpj('11222333000181'), new Telefone('1932427180'), new Endereco('13015-001', 'Campinas', Uf::SP, 'Rua 1'), $linha, Plano::Rede, Cobranca::Anual, new ResponsavelTecnico('Marina Alves', new RegistroProfissional('conselho', '123', Uf::SP), null, null), new DateTimeImmutable('2026-11-06T12:00:00-03:00'));
}
it('inicia exatamente catorze dias de teste em UTC e preserva o plano escolhido', function (): void {
    $lab = criarLaboratorio();
    expect($lab->nome)->toBe('Laboratório Alfa Vet')->and($lab->testeTerminaEm->format('c'))->toBe('2026-11-20T15:00:00+00:00')->and($lab->plano)->toBe(Plano::Rede)->and($lab->cobranca)->toBe(Cobranca::Anual);
});
it('restaura sem renovar o período de teste', function (): void {
    $lab = criarLaboratorio();
    $restaurado = Laboratorio::restaurar($lab->id, $lab->nome, $lab->cnpj, $lab->telefone, $lab->endereco, $lab->linha, $lab->plano, $lab->cobranca, $lab->rt, $lab->testeTerminaEm);
    expect($restaurado)->toEqual($lab);
});
it('não registra a linha clínica ainda indisponível', function (): void {
    criarLaboratorio(LinhaDeProduto::Clinica);
})->throws(InvalidValue::class);
it('calcula as franquias e o anual como dez mensalidades', function (Plano $plano, int $preco, int $pedidos, ?int $usuarios): void {
    expect($plano->precoEmCentavos())->toBe($preco)->and($plano->precoEmCentavos(Cobranca::Anual))->toBe($preco * 10)->and($plano->pedidos())->toBe($pedidos)->and($plano->usuarios())->toBe($usuarios);
})->with([[Plano::Bancada, 29000, 600, 3], [Plano::Laboratorio, 59000, 2000, 10], [Plano::Rede, 119000, 6000, null]]);
