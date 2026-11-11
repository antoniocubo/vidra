<?php

declare(strict_types=1);

use Vidra\Laudos\Domain\AvisoDeValorCritico;
use Vidra\Laudos\Domain\CodigoDeAcesso;
use Vidra\Laudos\Domain\ErroDeLiberacao;
use Vidra\Laudos\Domain\Laudo;
use Vidra\Laudos\Domain\PedidoParaConferencia;
use Vidra\Laudos\Domain\ResultadoParaConferencia;

function pedidoConferenciaUnit(string $situacao = 'resultado_digitado', bool $critico = false, bool $avisado = false): PedidoParaConferencia
{
    $data = new DateTimeImmutable('2026-11-08T12:00:00Z');
    $aviso = $avisado ? new AvisoDeValorCritico('aviso', 'lab', 'pedido', 'resultado', '10', $data, 1, $data, 'Dr. Rafael', '1932410099') : null;
    $r = new ResultadoParaConferencia('resultado', 'item', 'Bioquímico', 'Potássio', '10', 'mEq/L', $critico ? 'critico_alto' : 'normal', '3 a 5', 'Bruno', $data, null, null, false, null, $aviso);

    return new PedidoParaConferencia('pedido', 'lab', 'P-00001', 'Thor', 'Dr. Rafael', '1932410099', ['item' => $situacao], [$r]);
}
function liberarUnit(PedidoParaConferencia $pedido, bool $rt = true): Laudo
{
    return Laudo::liberar('laudo', $pedido, 1, 1, 'Marina', 'Marina', 'CRMV-SP 12345', new DateTimeImmutable('2026-11-08T13:00:00Z'), null, CodigoDeAcesso::gerar(), $rt);
}
it('gera códigos no alfabeto e no formato pedidos', function (): void {
    for ($i = 0; $i < 100; $i++) {
        expect(CodigoDeAcesso::gerar()->value)->toMatch('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{4}-[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{4}$/');
    }
});
it('recusa letras ambíguas no código', function (): void {
    new CodigoDeAcesso('I0O1-M2PX');
})->throws(Vidra\Shared\Domain\ValueObjects\InvalidValue::class);
it('impede liberar crítico sem aviso', function (): void {
    liberarUnit(pedidoConferenciaUnit(critico: true));
})->throws(ErroDeLiberacao::class, 'Registre o aviso do valor crítico de Potássio antes de liberar.');
it('impede liberar por quem não é RT', function (): void {
    liberarUnit(pedidoConferenciaUnit(), false);
})->throws(ErroDeLiberacao::class, 'Só o responsável técnico libera laudos.');
it('impede liberar item não digitado', function (): void {
    liberarUnit(pedidoConferenciaUnit('em_analise'));
})->throws(ErroDeLiberacao::class);
it('libera com aviso congelado e identidade do RT', function (): void {
    $l = liberarUnit(pedidoConferenciaUnit(critico: true, avisado: true));
    expect($l->numeroFormatado())->toBe('L-00001')->and($l->avisos)->toHaveCount(1)->and($l->rtRegistro)->toBe('CRMV-SP 12345');
});

function retificacaoUnit(string $motivo = 'Potássio digitado errado'): Vidra\Laudos\Domain\Retificacao
{
    return new Vidra\Laudos\Domain\Retificacao($motivo, 1, new DateTimeImmutable('2026-11-09T10:00:00Z'));
}

it('retifica gerando a versão 2 com o mesmo número e código', function (): void {
    $v1 = liberarUnit(pedidoConferenciaUnit());
    $reaberto = $v1->reabrir(retificacaoUnit(), true);
    $v2 = $reaberto->liberarRetificacao(pedidoConferenciaUnit(), 1, 'Marina', 'Marina', 'CRMV-SP 12345', new DateTimeImmutable('2026-11-09T11:00:00Z'), null, true);

    expect($reaberto->emRetificacao())->toBeTrue()
        ->and($v2->versao)->toBe(2)
        ->and($v2->emRetificacao())->toBeFalse()
        ->and($v2->retificacao?->motivo)->toBe('Potássio digitado errado')
        ->and($v2->codigoAcesso)->toEqual($v1->codigoAcesso)
        ->and($v2->versaoFormatada())->toBe('L-00001 v2')
        ->and($v1->versaoFormatada())->toBe('L-00001');
});

it('só o RT reabre o laudo e exige motivo', function (): void {
    $v1 = liberarUnit(pedidoConferenciaUnit());
    expect(fn () => $v1->reabrir(retificacaoUnit(), false))->toThrow(ErroDeLiberacao::class, 'Só o responsável técnico retifica laudos.')
        ->and(fn () => retificacaoUnit(' '))->toThrow(ErroDeLiberacao::class, 'Diga o motivo da retificação');
});

it('não reabre duas vezes nem libera versão sem reabrir', function (): void {
    $v1 = liberarUnit(pedidoConferenciaUnit());
    expect(fn () => $v1->reabrir(retificacaoUnit(), true)->reabrir(retificacaoUnit(), true))->toThrow(ErroDeLiberacao::class, 'já está aberto')
        ->and(fn () => $v1->liberarRetificacao(pedidoConferenciaUnit(), 1, 'M', 'M', 'R', new DateTimeImmutable, null, true))->toThrow(ErroDeLiberacao::class, 'Reabra o laudo');
});

it('a versão nova passa pelas mesmas conferências', function (): void {
    $reaberto = liberarUnit(pedidoConferenciaUnit())->reabrir(retificacaoUnit(), true);
    $reaberto->liberarRetificacao(pedidoConferenciaUnit(critico: true), 1, 'M', 'M', 'R', new DateTimeImmutable, null, true);
})->throws(ErroDeLiberacao::class, 'Registre o aviso do valor crítico');
