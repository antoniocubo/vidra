<?php

declare(strict_types=1);

use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Shared\Domain\ValueObjects\Uf;
use Vidra\Veterinaria\Domain\Veterinario\Crmv;

it('formata como aparece no laudo', function (): void {
    expect((string) new Crmv('41208', Uf::SP))->toBe('CRMV-SP 41208');
});

it('ignora pontuação e zeros à esquerda', function (): void {
    expect(new Crmv('04.120-8', Uf::SP)->numero)->toBe('41208');
});

it('compara número e UF', function (): void {
    expect(new Crmv('41208', Uf::SP)->equals(new Crmv('41208', Uf::SP)))->toBeTrue()
        ->and(new Crmv('41208', Uf::SP)->equals(new Crmv('41208', Uf::MG)))->toBeFalse();
});

it('recusa número vazio ou longo demais', function (string $numero): void {
    new Crmv($numero, Uf::SP);
})->with(['', 'abc', '1234567'])->throws(InvalidValue::class);
