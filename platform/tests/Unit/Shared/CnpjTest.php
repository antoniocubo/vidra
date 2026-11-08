<?php

declare(strict_types=1);

use Vidra\Shared\Domain\ValueObjects\Cnpj;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;

it('normaliza o CNPJ com pontuação', function (): void {
    expect((new Cnpj('11.222.333/0001-81'))->value)->toBe('11222333000181');
});
it('recusa CNPJ inválido sem aceitar dígitos repetidos', function (string $valor): void {
    new Cnpj($valor);
})->with(['11222333000180', '11222333000191', '00000000000000', '11111111111111', '123', 'abc11222333000181'])->throws(InvalidValue::class);
it('aceita CNPJ sem pontuação', function (): void {
    expect((new Cnpj('04252011000110'))->value)->toBe('04252011000110');
});
