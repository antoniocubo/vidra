<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\IncluirExame;

use Vidra\Auditoria\Application\RegistrarNaTrilha;
use Vidra\Pedidos\Domain\Amostra\AmostraRepository;
use Vidra\Pedidos\Domain\Amostra\AmostrasDoPedido;
use Vidra\Pedidos\Domain\Pedido\Exceptions\PedidoNaoEncontrado;
use Vidra\Pedidos\Domain\Pedido\Exceptions\ReferenciaInvalida;
use Vidra\Pedidos\Domain\Pedido\ItemDoPedido;
use Vidra\Pedidos\Domain\Pedido\PedidoId;
use Vidra\Pedidos\Domain\Pedido\PedidoRepository;
use Vidra\Pedidos\Domain\Portas\CatalogoDeExames;
use Vidra\Pedidos\Domain\Portas\Pagadores;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Shared\Domain\Transacao;

final readonly class IncluirExame
{
    public function __construct(private PedidoRepository $pedidos, private AmostraRepository $amostras,
        private CatalogoDeExames $catalogo, private Pagadores $pagadores,
        private LaboratorioAtual $laboratorio, private Transacao $transacao, private RegistrarNaTrilha $trilha) {}

    /** Devolve se uma amostra nova precisa de etiqueta. */
    public function __invoke(IncluirExameInput $input): bool
    {
        $laboratorioId = $this->laboratorio->id();

        $resultado = $this->transacao->executar(function () use ($input, $laboratorioId): array {
            $pedido = $this->pedidos->porId(new PedidoId($input->pedido)) ?? throw PedidoNaoEncontrado::para();
            if ($this->pedidos->temLaudo($pedido->id)) {
                throw \Vidra\Pedidos\Domain\Pedido\Exceptions\PedidoInvalido::jaLiberado();
            }
            if ($pedido->laboratorioId !== $laboratorioId) {
                throw PedidoNaoEncontrado::para();
            }
            $desconto = $pedido->pagador->clinicaId === null ? 0 : ($this->pagadores->clinica($pedido->pagador->clinicaId)->descontoPercentual ?? 0);
            $exame = $this->catalogo->ativo($input->exameId) ?? throw ReferenciaInvalida::exame();
            $item = ItemDoPedido::cotar($this->pedidos->nextItemIdentity(), $exame, $desconto);
            $pedido->adicionarItem($item);
            $existentes = $this->amostras->doPedido($pedido->id);
            $amostra = AmostrasDoPedido::incluir($pedido, $item, $existentes, $this->amostras);
            $nova = ! in_array($amostra, $existentes, true);
            $this->pedidos->save($pedido);
            $this->amostras->save($amostra);

            return [$nova, $exame->nome];
        });
        ($this->trilha)('exame_incluido', 'pedido', $input->pedido, 'Incluiu '.$resultado[1], $input->pedido);

        return $resultado[0];
    }
}
