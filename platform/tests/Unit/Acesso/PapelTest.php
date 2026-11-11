<?php

declare(strict_types=1);

use Vidra\Acesso\Domain\Papel;

it('dá ao administrador todas as áreas', function (): void {
    expect(Papel::Admin->areas())->toBe(Papel::AREAS);
});

it('só deixa a equipe e o financeiro com o administrador', function (Papel $papel): void {
    expect($papel->pode('equipe'))->toBeFalse()->and($papel->pode('financeiro'))->toBeFalse();
})->with([Papel::Rt, Papel::Bancada, Papel::Recepcao]);

it('não deixa a bancada abrir pedido nem liberar laudo', function (): void {
    expect(Papel::Bancada->pode('pedidos'))->toBeFalse()
        ->and(Papel::Bancada->pode('liberacao'))->toBeFalse()
        ->and(Papel::Bancada->pode('bancada'))->toBeTrue();
});

it('não deixa a recepção digitar resultado nem liberar laudo', function (): void {
    expect(Papel::Recepcao->pode('bancada'))->toBeFalse()
        ->and(Papel::Recepcao->pode('liberacao'))->toBeFalse()
        ->and(Papel::Recepcao->pode('pedidos'))->toBeTrue();
});

it('só usa áreas conhecidas', function (Papel $papel): void {
    expect(array_diff($papel->areas(), Papel::AREAS))->toBe([]);
})->with(Papel::cases());
