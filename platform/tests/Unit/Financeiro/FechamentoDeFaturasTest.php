<?php

declare(strict_types=1);

use Tests\Support\ArquivosDeFaturaEmMemoria;
use Tests\Support\EmissorDeFaturaEmMemoria;
use Tests\Support\GeradorDePdfDaFaturaFalso;
use Tests\Support\InMemoryFaturaRepository;
use Tests\Support\LaboratorioAtualFixo;
use Tests\Support\PedidosFaturaveisFixos;
use Tests\Support\TransacaoEmMemoria;
use Vidra\Financeiro\Application\ConcederCredito;
use Vidra\Financeiro\Application\ConcederCreditoInput;
use Vidra\Financeiro\Application\EnviarFatura\EnviarFatura;
use Vidra\Financeiro\Application\EnviarFatura\ReenviarFatura;
use Vidra\Financeiro\Application\FecharFaturas\FechadorDeFaturas;
use Vidra\Financeiro\Application\FecharFaturas\FecharFaturasAgora;
use Vidra\Financeiro\Application\FecharFaturas\FecharFaturasDoDia;
use Vidra\Financeiro\Application\RegistrarPagamento;
use Vidra\Financeiro\Domain\Calendario;
use Vidra\Financeiro\Domain\DadosDoPagador;
use Vidra\Financeiro\Domain\FaturaInvalida;
use Vidra\Financeiro\Domain\FaturaPendente;
use Vidra\Financeiro\Domain\LinhaDaFatura;
use Vidra\Financeiro\Domain\PagadorDaFatura;
use Vidra\Financeiro\Domain\SituacaoDaFatura;
use Vidra\Financeiro\Domain\TipoDePagadorDaFatura;

function dia(string $iso): DateTimeImmutable
{
    return new DateTimeImmutable($iso, new DateTimeZone('America/Sao_Paulo'));
}

function pendenteDaClinica(int $fechamento = 1, int $vencimento = 10, ?string $email = 'financeiro@patas.test'): FaturaPendente
{
    return new FaturaPendente(
        new DadosDoPagador(new PagadorDaFatura(TipoDePagadorDaFatura::Clinica, 'c1', 'Clínica Patas', $email), $fechamento, $vencimento),
        [new LinhaDaFatura('p1', 'P-00001', 'Thor', new DateTimeImmutable('2026-11-10T15:00:00Z'), 'Hemograma', 4200)],
    );
}

function cenarioDeFatura(FaturaPendente ...$pendentes): object
{
    $c = new stdClass;
    $c->faturas = new InMemoryFaturaRepository;
    $c->pedidos = new PedidosFaturaveisFixos(array_values($pendentes));
    $c->emissor = new EmissorDeFaturaEmMemoria;
    $c->arquivos = new ArquivosDeFaturaEmMemoria;
    $c->fechador = new FechadorDeFaturas($c->pedidos, $c->faturas, $c->emissor, new LaboratorioAtualFixo, new TransacaoEmMemoria);

    return $c;
}

it('fecha no dia combinado e no último dia do mês quando o dia não existe', function (): void {
    expect(Calendario::fechaNoDia(15, dia('2026-12-15 10:00')))->toBeTrue()
        ->and(Calendario::fechaNoDia(15, dia('2026-12-16 10:00')))->toBeTrue() // recupera um dia sem agendador
        ->and(Calendario::fechaNoDia(15, dia('2026-12-14 10:00')))->toBeFalse()
        ->and(Calendario::fechaNoDia(31, dia('2027-02-28 10:00')))->toBeTrue()
        ->and(Calendario::fechaNoDia(31, dia('2027-02-27 10:00')))->toBeFalse()
        ->and(Calendario::fechaNoDia(31, dia('2026-11-30 10:00')))->toBeTrue()
        ->and(Calendario::fechaNoDia(30, dia('2027-02-28 10:00')))->toBeTrue();
});

it('vence no mês do fechamento ou no seguinte quando o dia já passou', function (): void {
    $venc = fn (int $f, int $v, string $quando): string => Calendario::vencimento($f, $v, dia($quando))->format('Y-m-d');

    expect($venc(5, 20, '2026-12-05 08:00'))->toBe('2026-12-20')
        ->and($venc(15, 10, '2026-12-15 08:00'))->toBe('2027-01-10')
        ->and($venc(10, 10, '2026-12-10 08:00'))->toBe('2027-01-10')
        ->and($venc(31, 10, '2027-02-28 08:00'))->toBe('2027-03-10')
        ->and($venc(1, 10, '2026-12-01 08:00'))->toBe('2026-12-10');
});

it('fecha a competência anterior só de quem fecha hoje e manda enviar', function (): void {
    $c = cenarioDeFatura(pendenteDaClinica(fechamento: 1, vencimento: 10));
    $fechadas = (new FecharFaturasDoDia($c->fechador))(dia('2026-12-01 06:00'));

    $fatura = $c->faturas->items['fatura-1'];
    expect($fechadas)->toBe(1)
        ->and($fatura->competencia->valor())->toBe('2026-11')
        ->and($fatura->numeroFormatado())->toBe('F-00001')
        ->and($fatura->total())->toBe(4200)
        ->and($fatura->vencimento->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('Y-m-d'))->toBe('2026-12-10')
        ->and($c->emissor->enfileiradas)->toBe(['fatura-1']);

    $outroDia = cenarioDeFatura(pendenteDaClinica(fechamento: 15));
    expect((new FecharFaturasDoDia($outroDia->fechador))(dia('2026-12-01 06:00')))->toBe(0)
        ->and($outroDia->faturas->items)->toBe([]);
});

it('não cria outra fatura do mesmo pagador na mesma competência', function (): void {
    $c = cenarioDeFatura(pendenteDaClinica());
    $fechar = new FecharFaturasDoDia($c->fechador);

    expect($fechar(dia('2026-12-01 06:00')))->toBe(1)
        ->and($fechar(dia('2026-12-01 07:00')))->toBe(0)
        ->and($c->faturas->items)->toHaveCount(1);
});

it('sem e-mail de faturamento a fatura fica aberta e sem envio na fila', function (): void {
    $c = cenarioDeFatura(pendenteDaClinica(email: null));
    (new FecharFaturasDoDia($c->fechador))(dia('2026-12-01 06:00'));

    expect($c->faturas->items['fatura-1']->situacao(new DateTimeImmutable))->toBe(SituacaoDaFatura::Aberta)
        ->and($c->emissor->enfileiradas)->toBe([]);
});

it('fecha agora sem esperar o dia de fechamento do pagador', function (): void {
    $c = cenarioDeFatura(pendenteDaClinica(fechamento: 20));

    expect((new FecharFaturasAgora($c->fechador))())->toBe(1);
});

it('envia a fatura: gera o PDF, guarda, manda e só então marca como enviada', function (): void {
    $c = cenarioDeFatura(pendenteDaClinica());
    (new FecharFaturasDoDia($c->fechador))(dia('2026-12-01 06:00'));
    $enviar = new EnviarFatura($c->faturas, new GeradorDePdfDaFaturaFalso, $c->arquivos, $c->emissor, Tests\Support\AuditoriaFixa::registrar());

    $c->emissor->falhar = true;
    expect(fn () => $enviar('fatura-1'))->toThrow(RuntimeException::class)
        ->and($c->faturas->items['fatura-1']->enviadaEm())->toBeNull();

    $c->emissor->falhar = false;
    $enviar('fatura-1');
    expect($c->faturas->items['fatura-1']->enviadaEm())->not->toBeNull()
        ->and($c->emissor->enviadas)->toBe(['fatura-1'])
        ->and($c->arquivos->guardados['fatura-1'])->toStartWith('%PDF-');
});

it('reenviar manda de novo sem mudar a situação e recusa quem não tem e-mail', function (): void {
    $c = cenarioDeFatura(pendenteDaClinica());
    (new FecharFaturasDoDia($c->fechador))(dia('2026-12-01 06:00'));
    $enviar = new EnviarFatura($c->faturas, new GeradorDePdfDaFaturaFalso, $c->arquivos, $c->emissor, Tests\Support\AuditoriaFixa::registrar());
    $enviar('fatura-1');
    $enviadaEm = $c->faturas->items['fatura-1']->enviadaEm();
    $c->emissor->enfileiradas = [];

    (new ReenviarFatura($c->faturas, $c->emissor))('fatura-1');
    $enviar('fatura-1');

    expect($c->emissor->enfileiradas)->toBe(['fatura-1'])
        ->and($c->emissor->enviadas)->toHaveCount(2)
        ->and($c->faturas->items['fatura-1']->enviadaEm())->toEqual($enviadaEm);

    $semEmail = cenarioDeFatura(pendenteDaClinica(email: null));
    (new FecharFaturasDoDia($semEmail->fechador))(dia('2026-12-01 06:00'));
    expect(fn () => (new ReenviarFatura($semEmail->faturas, $semEmail->emissor))('fatura-1'))->toThrow(FaturaInvalida::class, 'e-mail de faturamento');
});

it('registra o pagamento depois do envio e não deixa pagar duas vezes', function (): void {
    $c = cenarioDeFatura(pendenteDaClinica());
    (new FecharFaturasDoDia($c->fechador))(dia('2026-12-01 06:00'));
    $registrar = new RegistrarPagamento($c->faturas, Tests\Support\AuditoriaFixa::registrar());

    expect(fn () => $registrar('fatura-1', 7))->toThrow(FaturaInvalida::class, 'Envie a fatura');

    (new EnviarFatura($c->faturas, new GeradorDePdfDaFaturaFalso, $c->arquivos, $c->emissor, Tests\Support\AuditoriaFixa::registrar()))('fatura-1');
    $registrar('fatura-1', 7);

    expect($c->faturas->items['fatura-1']->pagaPor())->toBe(7)
        ->and(fn () => $registrar('fatura-1', 7))->toThrow(FaturaInvalida::class, 'já está paga')
        ->and(fn () => $registrar('nao-existe', 7))->toThrow(FaturaInvalida::class, 'não encontrada');
});

it('concede crédito e recusa o que passa do total', function (): void {
    $c = cenarioDeFatura(pendenteDaClinica());
    (new FecharFaturasDoDia($c->fechador))(dia('2026-12-01 06:00'));
    (new EnviarFatura($c->faturas, new GeradorDePdfDaFaturaFalso, $c->arquivos, $c->emissor, Tests\Support\AuditoriaFixa::registrar()))('fatura-1');
    $conceder = new ConcederCredito($c->faturas, Tests\Support\AuditoriaFixa::registrar());

    $conceder(new ConcederCreditoInput('fatura-1', 'Exame repetido por falha nossa', 1000, 7));

    expect($c->faturas->items['fatura-1']->total())->toBe(3200)
        ->and(fn () => $conceder(new ConcederCreditoInput('fatura-1', 'Exagero', 9999, 7)))->toThrow(FaturaInvalida::class, 'O crédito não pode passar do total da fatura.');
});
