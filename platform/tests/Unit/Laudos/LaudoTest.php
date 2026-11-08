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
