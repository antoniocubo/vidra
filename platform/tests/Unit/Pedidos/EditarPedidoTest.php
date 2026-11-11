<?php

declare(strict_types=1);

use Tests\Support\InMemoryAmostraRepository;
use Tests\Support\InMemoryPedidoRepository;
use Tests\Support\LaboratorioAtualFixo;
use Tests\Support\PortasDePedidosFixas as Fixas;
use Tests\Support\RelogioDePedidosFixo;
use Tests\Support\TransacaoEmMemoria;
use Vidra\Pedidos\Application\AbrirPedido\AbrirPedido;
use Vidra\Pedidos\Application\AbrirPedido\AbrirPedidoInput;
use Vidra\Pedidos\Application\CancelarExame\CancelarExame;
use Vidra\Pedidos\Application\CancelarExame\CancelarExameInput;
use Vidra\Pedidos\Application\CancelarPedido\CancelarPedido;
use Vidra\Pedidos\Application\CancelarPedido\CancelarPedidoInput;
use Vidra\Pedidos\Application\IncluirExame\IncluirExame;
use Vidra\Pedidos\Application\IncluirExame\IncluirExameInput;
use Vidra\Pedidos\Domain\Amostra\AmostrasDoPedido;
use Vidra\Pedidos\Domain\Amostra\Exceptions\TriagemInvalida;
use Vidra\Pedidos\Domain\Amostra\MotivoDeRejeicao;
use Vidra\Pedidos\Domain\Pedido\Exceptions\PedidoInvalido;
use Vidra\Pedidos\Domain\Pedido\Exceptions\PedidoNaoEncontrado;
use Vidra\Pedidos\Domain\Pedido\Exceptions\ReferenciaInvalida;
use Vidra\Pedidos\Domain\Pedido\ItemDoPedido;
use Vidra\Pedidos\Domain\Pedido\PedidoId;
use Vidra\Pedidos\Domain\Pedido\SituacaoDoItem;
use Vidra\Pedidos\Domain\Pedido\SituacaoDoPedido;

beforeEach(function (): void {
    $this->pedidos = new InMemoryPedidoRepository;
    $this->amostras = new InMemoryAmostraRepository;
    $this->laboratorio = new LaboratorioAtualFixo;
    $this->transacao = new TransacaoEmMemoria;
    $this->relogio = new RelogioDePedidosFixo;
    $abrir = new AbrirPedido($this->pedidos, $this->amostras, Fixas::pacientes(), Fixas::solicitantes(), Fixas::pagadores(), Fixas::catalogo(), $this->laboratorio, $this->transacao, $this->relogio);
    $aberto = $abrir(new AbrirPedidoInput(Fixas::THOR, Fixas::RAFAEL, 'clinica', Fixas::PATAS, 'rotina', [Fixas::BIOQUIMICO, Fixas::HEMOGRAMA], null, 7));
    $this->pedido = $this->pedidos->porId(new PedidoId($aberto->pedidoId));
    $this->cancelarExame = new CancelarExame($this->pedidos, $this->amostras, $this->laboratorio, $this->transacao, $this->relogio);
    $this->cancelarPedido = new CancelarPedido($this->pedidos, $this->amostras, $this->laboratorio, $this->transacao, $this->relogio);
    $this->incluir = new IncluirExame($this->pedidos, $this->amostras, Fixas::catalogo(), Fixas::pagadores(), $this->laboratorio, $this->transacao);
});

it('agrupa o exame incluído pelo material normalizado e aplica o desconto atual da clínica', function (): void {
    $nova = ($this->incluir)(new IncluirExameInput($this->pedido->id->value, Fixas::RENAL, 7));
    expect($nova)->toBeFalse()
        ->and($this->amostras->doPedido($this->pedido->id))->toHaveCount(2)
        ->and($this->amostras->doPedido($this->pedido->id)[0]->itemIds)->toHaveCount(2)
        ->and($this->pedido->itens()[2]->precoCentavos)->toBe(3150)
        ->and($this->pedido->itens()[2]->situacao)->toBe(SituacaoDoItem::AguardandoAmostra);
});

it('gera nova amostra depois da maior sequência sem reaproveitar uma recebida', function (): void {
    $amostras = $this->amostras->doPedido($this->pedido->id);
    $amostras[0]->receber($this->relogio->agora(), 7);
    // A ordem das amostras não precisa coincidir com a sequência.
    $item = ItemDoPedido::cotar($this->pedidos->nextItemIdentity(), Fixas::catalogo()->ativo(Fixas::RENAL));
    $nova = AmostrasDoPedido::incluir($this->pedido, $item, array_reverse($amostras), $this->amostras);
    expect($nova->codigo->value)->toBe('A-0001-3')->and($nova->itemIds)->toBe([$item->id->value])
        ->and($amostras[0]->itemIds)->toHaveCount(1);
});

it('avisa que precisa imprimir etiqueta quando não existe amostra pendente do material', function (): void {
    $this->amostras->doPedido($this->pedido->id)[0]->receber($this->relogio->agora(), 7);
    expect(($this->incluir)(new IncluirExameInput($this->pedido->id->value, Fixas::RENAL, 7)))->toBeTrue()
        ->and($this->amostras->doPedido($this->pedido->id)[2]->codigo->value)->toBe('A-0001-3');
});

it('não inclui nem retira itens de uma amostra que já saiu da situação pendente', function (string $situacao): void {
    $amostra = $this->amostras->doPedido($this->pedido->id)[0];
    $amostra->receber($this->relogio->agora(), 7);
    if ($situacao === 'rejeitada') {
        $amostra->rejeitar(MotivoDeRejeicao::Hemolise);
    } elseif ($situacao === 'aceita_com_ressalva') {
        $amostra->aceitarComRessalva('Volume reduzido');
    }
    expect(fn () => $amostra->incluirItem('novo'))->toThrow(TriagemInvalida::class);
    expect(fn () => $amostra->retirarItem($amostra->itemIds[0]))->toThrow(TriagemInvalida::class);
})->with(['recebida', 'rejeitada', 'aceita_com_ressalva']);

it('retira apenas o item cancelado das amostras pendentes e preserva as recebidas', function (): void {
    $amostras = $this->amostras->doPedido($this->pedido->id);
    $amostras[1]->receber($this->relogio->agora(), 7);
    AmostrasDoPedido::retirar($amostras[1]->itemIds[0], $amostras);
    expect($amostras[1]->itemIds)->toHaveCount(1);
    AmostrasDoPedido::retirar($amostras[0]->itemIds[0], $amostras);
    expect($amostras[0]->itemIds)->toBeEmpty();
});

it('esvazia todas as pendentes ao cancelar sem alterar amostra recebida', function (): void {
    $amostras = $this->amostras->doPedido($this->pedido->id);
    $amostras[1]->receber($this->relogio->agora(), 7);
    ($this->cancelarPedido)(new CancelarPedidoInput($this->pedido->id->value, 'Solicitação da clínica', 9));
    expect($this->pedido->situacao())->toBe(SituacaoDoPedido::Cancelado)
        ->and($amostras[0]->itemIds)->toBeEmpty()->and($amostras[1]->itemIds)->toHaveCount(1)
        ->and($this->pedido->itens()[0]->cancelamento->por)->toBe(9)
        ->and($this->pedido->itens()[0]->cancelamento->em)->toEqual($this->relogio->agora());
});

it('salva o motivo e retira o exame cancelado da amostra pendente', function (): void {
    $item = $this->pedido->itens()[0];
    ($this->cancelarExame)(new CancelarExameInput($this->pedido->id->value, $item->id->value, '  Exame dispensado  ', 9));
    expect($this->pedido->itens()[0]->cancelamento->motivo)->toBe('Exame dispensado')
        ->and($this->pedido->total())->toBe(4200)
        ->and($this->amostras->doPedido($this->pedido->id)[0]->itemIds)->toBeEmpty();
});

it('recusa exame repetido ou desconhecido sem criar outra amostra', function (): void {
    expect(fn () => ($this->incluir)(new IncluirExameInput($this->pedido->id->value, Fixas::BIOQUIMICO, 7)))->toThrow(PedidoInvalido::class);
    expect(fn () => ($this->incluir)(new IncluirExameInput($this->pedido->id->value, Fixas::THOR, 7)))->toThrow(ReferenciaInvalida::class);
    expect($this->pedido->itens())->toHaveCount(2)->and($this->amostras->items)->toHaveCount(2);
});

it('não altera um pedido de outro laboratório mesmo com repositório em memória', function (): void {
    $outroLab = new LaboratorioAtualFixo('019a5d70-0000-7000-8000-000000000099');
    $cancelar = new CancelarPedido($this->pedidos, $this->amostras, $outroLab, $this->transacao, $this->relogio);
    $cancelarExame = new CancelarExame($this->pedidos, $this->amostras, $outroLab, $this->transacao, $this->relogio);
    $incluir = new IncluirExame($this->pedidos, $this->amostras, Fixas::catalogo(), Fixas::pagadores(), $outroLab, $this->transacao);
    expect(fn () => $cancelar(new CancelarPedidoInput($this->pedido->id->value, 'Motivo', 7)))->toThrow(PedidoNaoEncontrado::class);
    expect(fn () => $cancelarExame(new CancelarExameInput($this->pedido->id->value, $this->pedido->itens()[0]->id->value, 'Motivo', 7)))->toThrow(PedidoNaoEncontrado::class);
    expect(fn () => $incluir(new IncluirExameInput($this->pedido->id->value, Fixas::RENAL, 7)))->toThrow(PedidoNaoEncontrado::class);
    expect($this->pedido->situacao())->toBe(SituacaoDoPedido::AguardandoAmostra);
});
