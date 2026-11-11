<?php

declare(strict_types=1);

use Vidra\Acesso\Domain\Convite;
use Vidra\Acesso\Domain\ConviteInvalido;
use Vidra\Acesso\Domain\Papel;

beforeEach(function (): void {
    $this->agora = new DateTimeImmutable('2026-11-10T12:00:00Z');
    $this->convite = Convite::emitir('id', 'lab', ' Ana Souza ', 'ANA@example.com', Papel::Rt, 'segredo', 1, $this->agora);
});

it('emite convite com e-mail normalizado hash e validade de sete dias', function (): void {
    expect($this->convite->nome)->toBe('Ana Souza')->and($this->convite->email)->toBe('ana@example.com')
        ->and($this->convite->tokenHash)->toBe(hash('sha256', 'segredo'))
        ->and($this->convite->expiraEm)->toEqual($this->agora->modify('+7 days'));
});

it('aceita convite uma única vez', function (): void {
    $this->convite->aceitar($this->agora);
    expect($this->convite->aceitoEm)->toEqual($this->agora);
    expect(fn () => $this->convite->aceitar($this->agora))->toThrow(ConviteInvalido::class);
});

it('não aceita convite no instante do vencimento ou depois dele', function (string $prazo): void {
    expect(fn () => $this->convite->aceitar($this->agora->modify($prazo)))->toThrow(ConviteInvalido::class);
})->with(['+7 days', '+8 days']);

it('não aceita convite cancelado', function (): void {
    $this->convite->cancelar($this->agora);
    expect(fn () => $this->convite->aceitar($this->agora))->toThrow(ConviteInvalido::class);
});

it('restaura todas as datas e impede cancelar convite aceito', function (): void {
    $c = $this->convite;
    $restaurado = Convite::restaurar($c->id, $c->laboratorioId, $c->nome, $c->email, $c->papel, $c->tokenHash, $c->convidadoPor, $c->expiraEm, $this->agora, null);
    expect($restaurado->aceitoEm)->toEqual($this->agora);
    expect(fn () => $restaurado->cancelar($this->agora))->toThrow(ConviteInvalido::class);
});
