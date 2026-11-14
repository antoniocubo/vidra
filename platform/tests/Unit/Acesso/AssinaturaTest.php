<?php

declare(strict_types=1);

use Vidra\Acesso\Domain\Assinatura\Assinatura;
use Vidra\Acesso\Domain\Assinatura\ConsumoDoMes;
use Vidra\Acesso\Domain\Assinatura\SituacaoDaAssinatura;
use Vidra\Acesso\Domain\Cobranca;
use Vidra\Acesso\Domain\Plano;

function assinatura(?string $pagoAte = null, Cobranca $cobranca = Cobranca::Mensal): Assinatura
{
    return Assinatura::restaurar('lab', Plano::Bancada, $cobranca, new DateTimeImmutable('2026-11-24T03:00:00Z'), $pagoAte === null ? null : new DateTimeImmutable($pagoAte));
}

function em(string $quando): DateTimeImmutable
{
    return new DateTimeImmutable($quando);
}

it('fica em teste por 14 dias e depois só consulta, sem perder os laudos', function (): void {
    $a = assinatura();

    expect($a->situacao(em('2026-11-20T12:00:00Z')))->toBe(SituacaoDaAssinatura::EmTeste)
        ->and($a->situacao(em('2026-11-24T03:00:00Z')))->toBe(SituacaoDaAssinatura::SomenteConsulta)
        ->and(SituacaoDaAssinatura::SomenteConsulta->abrePedido())->toBeFalse()
        ->and(SituacaoDaAssinatura::Atrasada->abrePedido())->toBeTrue();
});

it('conta os dias restantes no horário de Brasília e avisa nos últimos cinco', function (): void {
    $a = assinatura();

    expect($a->diasRestantes(em('2026-11-18T12:00:00Z')))->toBe(6)
        ->and($a->deveAvisar(em('2026-11-18T12:00:00Z')))->toBeFalse()
        ->and($a->diasRestantes(em('2026-11-19T12:00:00Z')))->toBe(5)
        ->and($a->deveAvisar(em('2026-11-19T12:00:00Z')))->toBeTrue()
        ->and($a->diasRestantes(em('2026-12-30T12:00:00Z')))->toBe(0);
});

it('pagamento no teste estende a partir do fim do teste, sem perder dias', function (): void {
    $a = assinatura();
    $a->registrarPagamento(em('2026-11-15T12:00:00Z'));

    expect($a->pagoAte()?->format('Y-m-d'))->toBe('2026-12-24')
        ->and($a->situacao(em('2026-12-01T12:00:00Z')))->toBe(SituacaoDaAssinatura::Ativa);
});

it('anual estende um ano; atrasado tem tolerância antes de só consulta', function (): void {
    $a = assinatura('2026-12-24T03:00:00Z', Cobranca::Anual);

    expect($a->situacao(em('2026-12-26T12:00:00Z')))->toBe(SituacaoDaAssinatura::Atrasada)
        ->and($a->deveAvisar(em('2026-12-26T12:00:00Z')))->toBeTrue()
        ->and($a->situacao(em('2026-12-29T03:00:00Z')))->toBe(SituacaoDaAssinatura::SomenteConsulta);

    $a->registrarPagamento(em('2027-01-10T12:00:00Z'));
    expect($a->pagoAte()?->format('Y-m-d'))->toBe('2028-01-10');
});

it('cobra R$ 0,25 por pedido acima do limite do plano', function (int $pedidos, int $excedente, int $mensal, int $anual): void {
    expect((new ConsumoDoMes(Plano::Bancada, Cobranca::Mensal, $pedidos))->excedente())->toBe($excedente)
        ->and((new ConsumoDoMes(Plano::Bancada, Cobranca::Mensal, $pedidos))->valorDoMesCentavos())->toBe($mensal)
        ->and((new ConsumoDoMes(Plano::Bancada, Cobranca::Anual, $pedidos))->valorDoMesCentavos())->toBe($anual);
})->with([
    'dentro do limite' => [599, 0, 24000, 0],
    'no limite' => [600, 0, 24000, 0],
    'um a mais' => [601, 1, 24025, 25],
    '400 a mais' => [1000, 400, 34000, 10000],
]);

it('o anual custa dez mensalidades (dois meses grátis)', function (): void {
    expect(Plano::Laboratorio->precoEmCentavos(Cobranca::Anual))->toBe(540000)
        ->and((new ConsumoDoMes(Plano::Rede, Cobranca::Mensal, 3000))->percentualUsado())->toBe(50);
});
