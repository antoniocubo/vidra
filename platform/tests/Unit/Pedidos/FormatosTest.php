<?php

declare(strict_types=1);

use Vidra\Pedidos\Domain\Amostra\CodigoDaAmostra;
use Vidra\Pedidos\Domain\Pedido\Exceptions\PagadorInvalido;
use Vidra\Pedidos\Domain\Pedido\Pagador;
use Vidra\Pedidos\Domain\Pedido\Protocolo;
use Vidra\Pedidos\Domain\Pedido\TipoDePagador;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;

it('mostra o protocolo com cinco dígitos', function (): void {
    expect((string) new Protocolo(412))->toBe('P-00412')
        ->and((string) new Protocolo(123456))->toBe('P-123456');
});

it('não aceita protocolo menor que 1', function (): void {
    new Protocolo(0);
})->throws(InvalidValue::class);

it('lê o protocolo digitado de várias formas', function (string $texto, ?int $esperado): void {
    expect(Protocolo::tentarLer($texto)?->numero)->toBe($esperado);
})->with([['P-00412', 412], ['p00412', 412], [' 412 ', 412], ['abc', null], ['P-0', null], ['A-0412-1', null]]);

it('monta o código da amostra com quatro dígitos do protocolo', function (): void {
    expect(CodigoDaAmostra::de(new Protocolo(412), 1)->value)->toBe('A-0412-1')
        ->and(CodigoDaAmostra::de(new Protocolo(7), 3)->value)->toBe('A-0007-3')
        ->and(CodigoDaAmostra::de(new Protocolo(12345), 2)->value)->toBe('A-12345-2')
        ->and(CodigoDaAmostra::de(new Protocolo(412), 3)->sequencia())->toBe(3);
});

it('lê o código bipado em minúsculas e rejeita o que não é código', function (): void {
    expect(CodigoDaAmostra::tentarLer(' a-0412-1 ')?->value)->toBe('A-0412-1')
        ->and(CodigoDaAmostra::tentarLer('P-00412'))->toBeNull()
        ->and(CodigoDaAmostra::tentarLer('A-12-1'))->toBeNull();
});

it('exige a clínica quando ela paga e só então', function (): void {
    expect(Pagador::clinica('c1')->ehClinica())->toBeTrue()
        ->and(Pagador::solicitante()->clinicaId)->toBeNull();

    expect(fn () => new Pagador(TipoDePagador::Clinica))->toThrow(PagadorInvalido::class, 'Escolha a clínica que recebe a fatura.');
    expect(fn () => new Pagador(TipoDePagador::Responsavel, 'c1'))->toThrow(PagadorInvalido::class);
});
