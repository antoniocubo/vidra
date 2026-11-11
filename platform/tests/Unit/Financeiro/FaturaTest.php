<?php

declare(strict_types=1);

use Vidra\Financeiro\Domain\Competencia;
use Vidra\Financeiro\Domain\Fatura;
use Vidra\Financeiro\Domain\FaturaInvalida;
use Vidra\Financeiro\Domain\LinhaDaFatura;
use Vidra\Financeiro\Domain\NotaDeCredito;
use Vidra\Financeiro\Domain\PagadorDaFatura;
use Vidra\Financeiro\Domain\SituacaoDaFatura;
use Vidra\Financeiro\Domain\TipoDePagadorDaFatura;

function linhaFatura(string $pedido, int $valor, string $quando = '2026-11-10T15:00:00Z'): LinhaDaFatura
{
    return new LinhaDaFatura($pedido, 'P-'.$pedido, 'Thor', new DateTimeImmutable($quando), 'Hemograma', $valor);
}

function faturaDeNovembro(?string $email = 'financeiro@clinica.test'): Fatura
{
    return Fatura::fechar('f1', 'lab', 7, new PagadorDaFatura(TipoDePagadorDaFatura::Clinica, 'c1', 'Clínica Patas', $email), new Competencia(2026, 11),
        new DateTimeImmutable('2026-12-10T12:00:00Z'), [linhaFatura('2', 5000, '2026-11-20T10:00:00Z'), linhaFatura('1', 4200)], new DateTimeImmutable('2026-12-01T09:00:00Z'));
}

function credito(int $valor): NotaDeCredito
{
    return new NotaDeCredito('Exame repetido por falha nossa', $valor, 1, new DateTimeImmutable('2026-12-05T10:00:00Z'));
}

it('calcula o mês em UTC a partir do horário do laboratório', function (): void {
    $novembro = new Competencia(2026, 11);

    expect($novembro->inicio()->format('Y-m-d H:i'))->toBe('2026-11-01 03:00')
        ->and($novembro->fim()->format('Y-m-d H:i'))->toBe('2026-12-01 03:00')
        ->and(Competencia::anteriorA(new DateTimeImmutable('2026-12-01T02:00:00Z'))->valor())->toBe('2026-10') // ainda 30/11 em SP
        ->and(Competencia::anteriorA(new DateTimeImmutable('2026-12-01T04:00:00Z'))->valor())->toBe('2026-11')
        ->and(Competencia::anteriorA(new DateTimeImmutable('2027-01-01T12:00:00Z'))->rotulo())->toBe('12/2026');
});

it('fecha a fatura em ordem de liberação e soma os pedidos', function (): void {
    $fatura = faturaDeNovembro();

    expect($fatura->linhas[0]->pedidoId)->toBe('1')
        ->and($fatura->total())->toBe(9200)
        ->and($fatura->numeroFormatado())->toBe('F-00007')
        ->and($fatura->situacao(new DateTimeImmutable('2026-12-01T10:00:00Z')))->toBe(SituacaoDaFatura::Aberta);
});

it('não fecha fatura vazia nem com pedido repetido', function (): void {
    $pagador = new PagadorDaFatura(TipoDePagadorDaFatura::Solicitante, 'v1', 'Dr. Rafael', null);
    expect(fn () => Fatura::fechar('f', 'lab', 1, $pagador, new Competencia(2026, 11), new DateTimeImmutable, [], new DateTimeImmutable))->toThrow(FaturaInvalida::class)
        ->and(fn () => Fatura::fechar('f', 'lab', 1, $pagador, new Competencia(2026, 11), new DateTimeImmutable, [linhaFatura('1', 1), linhaFatura('1', 1)], new DateTimeImmutable))->toThrow(FaturaInvalida::class);
});

it('passa de enviada a vencida no dia seguinte ao vencimento, no horário de Brasília', function (): void {
    $fatura = faturaDeNovembro();
    $fatura->enviar(new DateTimeImmutable('2026-12-01T10:00:00Z'));

    expect($fatura->situacao(new DateTimeImmutable('2026-12-11T02:59:00Z')))->toBe(SituacaoDaFatura::Enviada) // 23:59 do dia 10 em SP
        ->and($fatura->situacao(new DateTimeImmutable('2026-12-11T03:00:00Z')))->toBe(SituacaoDaFatura::Vencida);
});

it('não envia sem e-mail de faturamento nem duas vezes', function (): void {
    expect(fn () => faturaDeNovembro(null)->enviar(new DateTimeImmutable))->toThrow(FaturaInvalida::class, 'e-mail de faturamento');
    $fatura = faturaDeNovembro();
    $fatura->enviar(new DateTimeImmutable);
    expect(fn () => $fatura->enviar(new DateTimeImmutable))->toThrow(FaturaInvalida::class, 'já foi enviada');
});

it('dá baixa manual só depois de enviada, e paga não volta a vencer', function (): void {
    $fatura = faturaDeNovembro();
    expect(fn () => $fatura->registrarPagamento(new DateTimeImmutable, 1))->toThrow(FaturaInvalida::class, 'Envie a fatura');

    $fatura->enviar(new DateTimeImmutable('2026-12-01T10:00:00Z'));
    $fatura->registrarPagamento(new DateTimeImmutable('2026-12-20T10:00:00Z'), 3);

    expect($fatura->situacao(new DateTimeImmutable('2027-01-30T10:00:00Z')))->toBe(SituacaoDaFatura::Paga)
        ->and($fatura->pagaPor())->toBe(3)
        ->and(fn () => $fatura->registrarPagamento(new DateTimeImmutable, 1))->toThrow(FaturaInvalida::class, 'já está paga');
});

it('ajusta a fatura enviada só por nota de crédito, sem passar do total', function (): void {
    $fatura = faturaDeNovembro();
    expect(fn () => $fatura->creditar(credito(100)))->toThrow(FaturaInvalida::class, 'Antes de enviar');

    $fatura->enviar(new DateTimeImmutable);
    $fatura->creditar(credito(1200));

    expect($fatura->subtotal())->toBe(9200)->and($fatura->totalCreditos())->toBe(1200)->and($fatura->total())->toBe(8000)
        ->and(fn () => $fatura->creditar(credito(8001)))->toThrow(FaturaInvalida::class, 'não pode passar do total')
        ->and(fn () => credito(0))->toThrow(FaturaInvalida::class);
});
