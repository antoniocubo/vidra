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
use Vidra\Pedidos\Application\PedirRecoleta\PedirRecoleta;
use Vidra\Pedidos\Application\ReceberAmostra\ReceberAmostra;
use Vidra\Pedidos\Application\RejeitarAmostra\RejeitarAmostra;
use Vidra\Pedidos\Application\RejeitarAmostra\RejeitarAmostraInput;
use Vidra\Pedidos\Domain\Amostra\Amostra;
use Vidra\Pedidos\Domain\Amostra\CodigoDaAmostra;
use Vidra\Pedidos\Domain\Amostra\Exceptions\AmostraNaoEncontrada;
use Vidra\Pedidos\Domain\Amostra\Exceptions\TriagemInvalida;
use Vidra\Pedidos\Domain\Amostra\MensagemDeRecoleta;
use Vidra\Pedidos\Domain\Amostra\MotivoDeRejeicao;
use Vidra\Pedidos\Domain\Amostra\SituacaoDaAmostra;
use Vidra\Pedidos\Domain\Pedido\Exceptions\ReferenciaInvalida;
use Vidra\Pedidos\Domain\Pedido\SituacaoDoItem;
use Vidra\Pedidos\Domain\Pedido\TipoDePagador;

beforeEach(function (): void {
    $this->pedidos = new InMemoryPedidoRepository;
    $this->amostras = new InMemoryAmostraRepository;
    $this->relogio = new RelogioDePedidosFixo;
    $this->abrir = new AbrirPedido($this->pedidos, $this->amostras, Fixas::pacientes(), Fixas::solicitantes(), Fixas::pagadores(), Fixas::catalogo(), new LaboratorioAtualFixo, new TransacaoEmMemoria, $this->relogio);
    $this->receber = new ReceberAmostra($this->amostras, $this->pedidos, Fixas::pacientes(), new TransacaoEmMemoria, $this->relogio);
    $this->rejeitar = new RejeitarAmostra($this->amostras, $this->pedidos, new TransacaoEmMemoria);
    $this->recoleta = new PedirRecoleta($this->amostras, $this->pedidos, new TransacaoEmMemoria);
    $this->abrirCom = fn (array $exames, string $pagador = 'clinica', ?string $clinica = Fixas::PATAS) => ($this->abrir)(new AbrirPedidoInput(Fixas::THOR, Fixas::RAFAEL, $pagador, $clinica, 'rotina', $exames, null, 7));
});

it('abre o pedido com protocolo, desconto da clínica e uma amostra por material', function (): void {
    $aberto = ($this->abrirCom)([Fixas::BIOQUIMICO, Fixas::HEMOGRAMA, Fixas::RENAL]);

    $pedido = $this->pedidos->items[$aberto->pedidoId];
    expect($aberto->protocolo)->toBe('P-00001')
        ->and($aberto->etiquetas)->toBe(2)
        ->and($pedido->total())->toBe(9600 + 4200 + 3150)
        ->and($pedido->pagador->tipo)->toBe(TipoDePagador::Clinica);

    $codigos = array_map(fn (Amostra $a): string => $a->codigo->value, array_values($this->amostras->items));
    expect($codigos)->toBe(['A-0001-1', 'A-0001-2'])
        ->and(array_values($this->amostras->items)[0]->itemIds)->toHaveCount(2);
});

it('cobra preço de tabela quando o veterinário ou o tutor pagam', function (): void {
    $aberto = ($this->abrirCom)([Fixas::BIOQUIMICO], 'responsavel', null);

    expect($this->pedidos->items[$aberto->pedidoId]->total())->toBe(10667);
});

it('recusa clínica inativa, exame fora do catálogo e paciente desconhecido', function (): void {
    $inativa = new AbrirPedido($this->pedidos, $this->amostras, Fixas::pacientes(), Fixas::solicitantes(), Fixas::pagadores(10, false), Fixas::catalogo(), new LaboratorioAtualFixo, new TransacaoEmMemoria, $this->relogio);
    expect(fn () => $inativa(new AbrirPedidoInput(Fixas::THOR, Fixas::RAFAEL, 'clinica', Fixas::PATAS, 'rotina', [Fixas::BIOQUIMICO], null, 7)))->toThrow(ReferenciaInvalida::class);
    expect(fn () => ($this->abrirCom)(['019a5d70-0000-7000-8000-0000000000ff']))->toThrow(ReferenciaInvalida::class);
    expect(fn () => ($this->abrir)(new AbrirPedidoInput('019a5d70-0000-7000-8000-0000000000ee', Fixas::RAFAEL, 'responsavel', null, 'rotina', [Fixas::BIOQUIMICO], null, 7)))->toThrow(ReferenciaInvalida::class);
    expect($this->pedidos->items)->toBeEmpty();
});

it('recebe a amostra, passa os exames para análise e avisa quem chegou', function (): void {
    $aberto = ($this->abrirCom)([Fixas::BIOQUIMICO, Fixas::HEMOGRAMA]);

    $recebida = ($this->receber)('a-0001-1', 7);

    expect($recebida->mensagem())->toBe('A-0001-1 recebida · Thor · Soro');
    $pedido = $this->pedidos->items[$aberto->pedidoId];
    expect($pedido->itens()[0]->situacao)->toBe(SituacaoDoItem::EmAnalise)
        ->and($pedido->itens()[1]->situacao)->toBe(SituacaoDoItem::AguardandoAmostra);
});

it('não recebe a mesma amostra duas vezes', function (): void {
    ($this->abrirCom)([Fixas::BIOQUIMICO]);
    ($this->receber)('A-0001-1', 7);

    expect(fn () => ($this->receber)('A-0001-1', 7))->toThrow(TriagemInvalida::class, 'A amostra A-0001-1 já foi recebida às 10:35.');
});

it('avisa quando o código não existe no laboratório', function (string $codigo): void {
    expect(fn () => ($this->receber)($codigo, 7))->toThrow(AmostraNaoEncontrada::class, "Nenhuma amostra com o código {$codigo} neste laboratório.");
})->with(['A-9999-1', 'LIXO']);

it('rejeita a amostra e devolve os exames para aguardar nova coleta', function (): void {
    $aberto = ($this->abrirCom)([Fixas::BIOQUIMICO]);
    ($this->receber)('A-0001-1', 7);
    $amostra = array_values($this->amostras->items)[0];

    ($this->rejeitar)(new RejeitarAmostraInput($amostra->id->value, 'hemolise'));

    expect($amostra->situacao())->toBe(SituacaoDaAmostra::Rejeitada)
        ->and($amostra->motivo())->toBe(MotivoDeRejeicao::Hemolise)
        ->and($this->pedidos->items[$aberto->pedidoId]->itens()[0]->situacao)->toBe(SituacaoDoItem::AguardandoAmostra);
});

it('pede recoleta só de amostra rejeitada e cria a próxima, com o mesmo material', function (): void {
    ($this->abrirCom)([Fixas::BIOQUIMICO, Fixas::HEMOGRAMA]);
    $amostra = array_values($this->amostras->items)[0];

    expect(fn () => ($this->recoleta)($amostra->id->value))->toThrow(TriagemInvalida::class, 'Só uma amostra rejeitada pede recoleta');

    ($this->receber)('A-0001-1', 7);
    ($this->rejeitar)(new RejeitarAmostraInput($amostra->id->value, 'volume_insuficiente'));
    $novo = ($this->recoleta)($amostra->id->value);

    expect($novo)->toBe('A-0001-3')
        ->and($amostra->recoleta()?->value)->toBe('A-0001-3');
    $nova = $this->amostras->porCodigo(new CodigoDaAmostra($novo));
    expect($nova->situacao())->toBe(SituacaoDaAmostra::Aguardando)
        ->and($nova->material)->toBe($amostra->material)
        ->and($nova->itemIds)->toBe($amostra->itemIds);

    expect(fn () => ($this->recoleta)($amostra->id->value))->toThrow(TriagemInvalida::class, 'já foi pedida');
});

it('aceita com ressalva só depois de receber e com texto', function (): void {
    ($this->abrirCom)([Fixas::BIOQUIMICO]);
    $amostra = array_values($this->amostras->items)[0];

    expect(fn () => $amostra->aceitarComRessalva('Lipêmica'))->toThrow(TriagemInvalida::class, 'ainda não foi recebida');

    ($this->receber)('A-0001-1', 7);
    expect(fn () => $amostra->aceitarComRessalva('  '))->toThrow(TriagemInvalida::class, 'Escreva a ressalva');

    $amostra->aceitarComRessalva(' Amostra levemente lipêmica ');
    expect($amostra->situacao())->toBe(SituacaoDaAmostra::AceitaComRessalva)
        ->and($amostra->ressalva())->toBe('Amostra levemente lipêmica');
});

it('exige o texto quando o motivo é outro', function (): void {
    ($this->abrirCom)([Fixas::BIOQUIMICO]);
    $amostra = array_values($this->amostras->items)[0];

    expect(fn () => $amostra->rejeitar(MotivoDeRejeicao::Outro))->toThrow(TriagemInvalida::class, 'Descreva o motivo');
});

it('monta a mensagem para a clínica', function (): void {
    expect(MensagemDeRecoleta::montar('A-0419-1', 'Nico', MotivoDeRejeicao::Hemolise, null, 'Soro', ['Bioquímico renal']))
        ->toBe('Amostra A-0419-1 do Nico chegou hemolisada. Precisamos de nova coleta de soro para Bioquímico renal.')
        ->and(MensagemDeRecoleta::montar('A-0419-1', 'Nico', MotivoDeRejeicao::Outro, 'Tubo trincado.', 'Sangue EDTA', ['Hemograma', 'Ureia']))
        ->toBe('Amostra A-0419-1 do Nico chegou com problema: Tubo trincado. Precisamos de nova coleta de sangue EDTA para Hemograma e Ureia.');
});
