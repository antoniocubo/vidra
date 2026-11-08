<?php

declare(strict_types=1);

use Vidra\Bancada\Domain\Classificacao;
use Vidra\Bancada\Domain\Decimal;
use Vidra\Bancada\Domain\DeltaCheck;
use Vidra\Bancada\Domain\Faixa;
use Vidra\Bancada\Domain\MotorDeReferencia;

it('classifica todas as fronteiras com comparações decimais exatas', function (string $valor, string $esperado): void {
    $f = new Faixa('perfil', new Decimal('5.5'), new Decimal('8.5'), new Decimal('2'), new Decimal('10'));
    expect((new MotorDeReferencia)->classificar(new Decimal($valor), $f)->value)->toBe($esperado);
})->with([['1.9999', 'critico_baixo'], ['2', 'critico_baixo'], ['2.0001', 'baixo'], ['5.4999', 'baixo'], ['5.5', 'normal'], ['8.5', 'normal'], ['8.5001', 'alto'], ['9.9999', 'alto'], ['10', 'critico_alto'], ['10.0001', 'critico_alto']]);
it('aceita referências parciais e sem limites críticos', function (): void {
    $m = new MotorDeReferencia;
    expect($m->classificar(new Decimal('2'), new Faixa('p', new Decimal('3'), null)))->toBe(Classificacao::Baixo)
        ->and($m->classificar(new Decimal('999'), new Faixa('p', new Decimal('3'), null)))->toBe(Classificacao::Normal)
        ->and($m->classificar(new Decimal('5'), new Faixa('p', null, new Decimal('4'))))->toBe(Classificacao::Alto)
        ->and($m->classificar(new Decimal('-5'), new Faixa('p', null, new Decimal('4'))))->toBe(Classificacao::Normal)
        ->and($m->classificar(new Decimal('100'), null))->toBe(Classificacao::SemReferencia);
});
it('normaliza o decimal sem perder precisão', function (): void {
    expect((new Decimal('-000.0000'))->value)->toBe('0')->and((new Decimal('000123.4500'))->value)->toBe('123.45')
        ->and((new Decimal('9999999999.9999'))->comparar(new Decimal('9999999999.9998')))->toBe(1);
});
it('recusa decimal inválido', function (string $valor): void {
    new Decimal($valor);
})->with(['1e2', 'NaN', '1.00001', '10000000000', '1,2', ''])->throws(Vidra\Shared\Domain\ValueObjects\InvalidValue::class);
it('alerta delta inclusivo inclusive zero e valores negativos', function (string $novo, string $anterior, bool $esperado): void {
    expect((new DeltaCheck)->alerta(new Decimal($novo), new Decimal($anterior)))->toBe($esperado);
})->with([['15', '10', true], ['14.9999', '10', false], ['5', '10', true], ['5.0001', '10', false], ['0', '0', false], ['1', '0', true], ['-1', '0', true], ['-15', '-10', true], ['-14.9999', '-10', false], ['-5', '-10', true], ['10', '-10', true]]);
