<?php

declare(strict_types=1);

use Vidra\Laudos\Application\MontarLaudo\FormatoDoLaudo as F;
use Vidra\Laudos\Domain\CodigoDeAcesso;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;

it('formata resultados com casas decimais e vírgula', function (): void {
    expect(F::resultado('2.4', 2))->toBe('2,40')->and(F::resultado('142', 0))->toBe('142')
        ->and(F::resultado(null, 2, 'Não reagente'))->toBe('Não reagente');
});
it('formata faixas abertas e fechadas e classificação com palavra', function (): void {
    expect(F::faixa('0.5 a 1.5'))->toBe('0,5 a 1,5')->and(F::faixa('≤ 1.5'))->toBe('até 1,5')->and(F::faixa('≥ 0.5'))->toBe('a partir de 0,5')
        ->and(F::classificacao('alto'))->toBe('↑ Alto')->and(F::classificacao('baixo'))->toBe('↓ Baixo')->and(F::classificacao('critico_baixo'))->toBe('▲ Crítico');
});
it('aceita código com ou sem hífen em maiúsculas e minúsculas', function (string $entrada): void {
    expect(CodigoDeAcesso::deEntrada($entrada)->value)->toBe('K7Q4-M2PX');
})->with(['K7Q4-M2PX', 'k7q4m2px', ' k7q4-m2px ']);
it('recusa código inválido', fn () => CodigoDeAcesso::deEntrada('invalid0'))->throws(InvalidValue::class);
it('monta o texto de WhatsApp e normaliza o celular brasileiro', function (): void {
    expect(F::whatsapp('Carla Mendes', 'Thor', 'https://laudo.test/K7Q4M2PX', 'K7Q4-M2PX'))->toBe('Olá, Carla! O laudo do Thor está pronto: https://laudo.test/K7Q4M2PX. Código de acesso: K7Q4-M2PX.')
        ->and(F::celular('(19) 99812-4410'))->toBe('5519998124410')->and(F::celular('5519998124410'))->toBe('5519998124410')->and(F::celular('123'))->toBeNull();
});
it('apresenta as datas em São Paulo', function (): void {
    expect(F::data(new DateTimeImmutable('2026-11-08 14:46:00+00:00')))->toBe('08/11/2026 11:46');
});
