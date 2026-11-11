<?php

declare(strict_types=1);

use Tests\Support\PortasDePedidosFixas as Fixas;
use Vidra\Pedidos\Domain\Pedido\Cancelamento;
use Vidra\Pedidos\Domain\Pedido\Exceptions\PedidoInvalido;
use Vidra\Pedidos\Domain\Pedido\ItemDoPedido;
use Vidra\Pedidos\Domain\Pedido\ItemId;
use Vidra\Pedidos\Domain\Pedido\Pagador;
use Vidra\Pedidos\Domain\Pedido\Pedido;
use Vidra\Pedidos\Domain\Pedido\PedidoId;
use Vidra\Pedidos\Domain\Pedido\Prioridade;
use Vidra\Pedidos\Domain\Pedido\Protocolo;
use Vidra\Pedidos\Domain\Pedido\SituacaoDoItem;
use Vidra\Pedidos\Domain\Pedido\SituacaoDoPedido;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;

function umItem(string $exame = Fixas::BIOQUIMICO, int $desconto = 0): ItemDoPedido
{
    return ItemDoPedido::cotar(new ItemId((string) Illuminate\Support\Str::uuid7()), Fixas::catalogo()->ativo($exame), $desconto);
}

/** @param list<ItemDoPedido> $itens */
function umPedido(array $itens): Pedido
{
    return Pedido::abrir(new PedidoId((string) Illuminate\Support\Str::uuid7()), Fixas::PATAS, new Protocolo(412), Fixas::THOR, Fixas::RAFAEL, Pagador::solicitante(), Prioridade::Rotina, new DateTimeImmutable('2026-11-08 13:00:00 UTC'), 1, '  ', $itens);
}

it('copia os dados do exame e aplica o desconto da clínica', function (): void {
    $item = umItem(Fixas::BIOQUIMICO, 10);

    expect($item->codigo)->toBe('BIOQ')
        ->and($item->precoTabelaCentavos)->toBe(10667)
        ->and($item->descontoPercentual)->toBe(10)
        ->and($item->precoCentavos)->toBe(9600)
        ->and($item->situacao)->toBe(SituacaoDoItem::AguardandoAmostra);
});

it('arredonda o preço com desconto para o centavo, metade para cima', function (int $preco, int $desconto, int $esperado): void {
    expect(ItemDoPedido::comDesconto($preco, $desconto))->toBe($esperado);
})->with([[10667, 10, 9600], [4667, 10, 4200], [1005, 50, 503], [1001, 50, 501], [999, 0, 999], [500, 100, 0], [333, 15, 283]]);

it('não aceita desconto fora de 0 a 100', function (): void {
    umItem(Fixas::BIOQUIMICO, 101);
})->throws(InvalidValue::class);

it('abre o pedido com os exames e soma o total', function (): void {
    $pedido = umPedido([umItem(Fixas::BIOQUIMICO, 10), umItem(Fixas::HEMOGRAMA, 10)]);

    expect($pedido->total())->toBe(9600 + 4200)
        ->and($pedido->observacao)->toBeNull()
        ->and($pedido->urgente())->toBeFalse()
        ->and($pedido->situacao())->toBe(SituacaoDoPedido::AguardandoAmostra);
});

it('exige pelo menos um exame', function (): void {
    umPedido([]);
})->throws(PedidoInvalido::class, 'Escolha pelo menos um exame.');

it('não deixa repetir o mesmo exame no pedido', function (): void {
    umPedido([umItem(), umItem()]);
})->throws(PedidoInvalido::class, 'O exame Bioquímico renal e hepático já está no pedido.');

it('deriva a situação do pedido dos itens', function (): void {
    $pedido = umPedido([umItem(Fixas::BIOQUIMICO), umItem(Fixas::HEMOGRAMA)]);
    $ids = array_map(fn (ItemDoPedido $i): string => $i->id->value, $pedido->itens());

    $pedido->iniciarAnaliseDe([$ids[0]]);
    expect($pedido->situacao())->toBe(SituacaoDoPedido::EmAnalise);

    $pedido->iniciarAnaliseDe($ids);
    expect($pedido->situacao())->toBe(SituacaoDoPedido::EmAnalise);

    $pedido->aguardarNovaAmostraDe($ids);
    expect($pedido->situacao())->toBe(SituacaoDoPedido::AguardandoAmostra);
});

it('deriva aguardando liberação, liberado e cancelado', function (): void {
    $itens = [umItem(Fixas::BIOQUIMICO), umItem(Fixas::HEMOGRAMA)];
    $com = fn (SituacaoDoItem ...$situacoes): Pedido => umPedido(array_map(
        fn (ItemDoPedido $item, SituacaoDoItem $s): ItemDoPedido => ItemDoPedido::restaurar($item->id, $item->exameId, $item->codigo, $item->nome, $item->setor, $item->material, $item->precoTabelaCentavos, 0, $item->precoCentavos, $s),
        $itens,
        $situacoes,
    ));

    expect($com(SituacaoDoItem::ResultadoDigitado, SituacaoDoItem::Liberado)->situacao())->toBe(SituacaoDoPedido::AguardandoLiberacao)
        ->and($com(SituacaoDoItem::Liberado, SituacaoDoItem::Liberado)->situacao())->toBe(SituacaoDoPedido::Liberado)
        ->and($com(SituacaoDoItem::Liberado, SituacaoDoItem::Cancelado)->situacao())->toBe(SituacaoDoPedido::Liberado)
        ->and($com(SituacaoDoItem::Cancelado, SituacaoDoItem::Cancelado)->situacao())->toBe(SituacaoDoPedido::Cancelado);
});

it('cancela um exame e tira do total, mas não o último', function (): void {
    $pedido = umPedido([umItem(Fixas::BIOQUIMICO, 10), umItem(Fixas::HEMOGRAMA, 10)]);
    [$primeiro, $segundo] = $pedido->itens();

    $pedido->cancelarItem($primeiro->id, umCancelamento());
    expect($pedido->total())->toBe(4200)
        ->and($pedido->item($primeiro->id)->situacao)->toBe(SituacaoDoItem::Cancelado)
        ->and($pedido->item($primeiro->id)->cancelamento?->motivo)->toBe('Veterinário desistiu');

    expect(fn () => $pedido->cancelarItem($segundo->id, umCancelamento()))->toThrow(PedidoInvalido::class);
});

function umCancelamento(string $motivo = 'Veterinário desistiu'): Cancelamento
{
    return new Cancelamento($motivo, 1, new DateTimeImmutable('2026-11-10 14:00', new DateTimeZone('America/Sao_Paulo')));
}

it('exige o motivo do cancelamento', function (): void {
    expect(fn () => umCancelamento('   '))->toThrow(PedidoInvalido::class, 'Diga o motivo do cancelamento.');
});

it('cancela o pedido inteiro, guardando quem e quando', function (): void {
    $pedido = umPedido([umItem(Fixas::BIOQUIMICO, 10), umItem(Fixas::HEMOGRAMA, 10)]);
    $pedido->cancelar(umCancelamento('Tutor desistiu'));

    expect($pedido->situacao())->toBe(SituacaoDoPedido::Cancelado)
        ->and($pedido->total())->toBe(0)
        ->and($pedido->itens()[1]->cancelamento?->em->getTimezone()->getName())->toBe('UTC');
    expect(fn () => $pedido->cancelar(umCancelamento()))->toThrow(PedidoInvalido::class, 'já estava cancelado');
});

it('não cancela depois da liberação', function (): void {
    $pedido = umPedido([umItem(Fixas::BIOQUIMICO, 10)]);
    $liberado = Pedido::restaurar($pedido->id, $pedido->laboratorioId, $pedido->protocolo, $pedido->pacienteId, $pedido->solicitanteId, $pedido->pagador, $pedido->prioridade, $pedido->criadoEm, $pedido->criadoPor, null, [comSituacao($pedido->itens()[0], SituacaoDoItem::Liberado)]);

    expect(fn () => $liberado->cancelar(umCancelamento()))->toThrow(PedidoInvalido::class, 'retifique o laudo')
        ->and(fn () => $liberado->adicionarItem(umItem(Fixas::HEMOGRAMA, 0)))->toThrow(PedidoInvalido::class);
});

it('inclui exame aguardando amostra e recusa o repetido', function (): void {
    $pedido = umPedido([umItem(Fixas::BIOQUIMICO, 10)]);
    $pedido->adicionarItem(umItem(Fixas::HEMOGRAMA, 0));

    expect($pedido->itens())->toHaveCount(2)
        ->and($pedido->itens()[1]->situacao)->toBe(SituacaoDoItem::AguardandoAmostra);
    expect(fn () => $pedido->adicionarItem(umItem(Fixas::HEMOGRAMA, 0)))->toThrow(PedidoInvalido::class, 'já está no pedido');
});

function comSituacao(ItemDoPedido $i, SituacaoDoItem $s): ItemDoPedido
{
    return ItemDoPedido::restaurar($i->id, $i->exameId, $i->codigo, $i->nome, $i->setor, $i->material, $i->precoTabelaCentavos, $i->descontoPercentual, $i->precoCentavos, $s);
}
