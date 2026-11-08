<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\AbrirPedido;

use Vidra\Pedidos\Domain\Amostra\AmostraRepository;
use Vidra\Pedidos\Domain\Amostra\AmostrasDoPedido;
use Vidra\Pedidos\Domain\Pedido\Exceptions\ReferenciaInvalida;
use Vidra\Pedidos\Domain\Pedido\ItemDoPedido;
use Vidra\Pedidos\Domain\Pedido\Pagador;
use Vidra\Pedidos\Domain\Pedido\Pedido;
use Vidra\Pedidos\Domain\Pedido\PedidoRepository;
use Vidra\Pedidos\Domain\Pedido\Prioridade;
use Vidra\Pedidos\Domain\Pedido\TipoDePagador;
use Vidra\Pedidos\Domain\Portas\CatalogoDeExames;
use Vidra\Pedidos\Domain\Portas\Pacientes;
use Vidra\Pedidos\Domain\Portas\Pagadores;
use Vidra\Pedidos\Domain\Portas\Solicitantes;
use Vidra\Pedidos\Domain\Relogio;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Shared\Domain\Transacao;

final readonly class AbrirPedido
{
    public function __construct(
        private PedidoRepository $pedidos,
        private AmostraRepository $amostras,
        private Pacientes $pacientes,
        private Solicitantes $solicitantes,
        private Pagadores $pagadores,
        private CatalogoDeExames $catalogo,
        private LaboratorioAtual $laboratorio,
        private Transacao $transacao,
        private Relogio $relogio,
    ) {}

    public function __invoke(AbrirPedidoInput $input): PedidoAberto
    {
        $laboratorioId = $this->laboratorio->id();
        $pagador = $this->pagador($input);
        $itens = $this->itens($input, $pagador);

        $this->pacientes->resumo($input->pacienteId) ?? throw ReferenciaInvalida::paciente();
        $this->solicitantes->resumo($input->solicitanteId) ?? throw ReferenciaInvalida::solicitante();

        return $this->transacao->executar(function () use ($input, $laboratorioId, $pagador, $itens): PedidoAberto {
            $pedido = Pedido::abrir(
                $this->pedidos->nextIdentity(),
                $laboratorioId,
                $this->pedidos->proximoProtocolo(),
                $input->pacienteId,
                $input->solicitanteId,
                $pagador,
                Prioridade::from($input->prioridade),
                $this->relogio->agora(),
                $input->criadoPor,
                $input->observacao,
                $itens,
            );
            $this->pedidos->save($pedido);

            $amostras = AmostrasDoPedido::gerar($pedido, $this->amostras);
            foreach ($amostras as $amostra) {
                $this->amostras->save($amostra);
            }

            return new PedidoAberto($pedido->id->value, (string) $pedido->protocolo, count($amostras));
        });
    }

    private function pagador(AbrirPedidoInput $input): Pagador
    {
        $tipo = TipoDePagador::from($input->pagador);
        if ($tipo !== TipoDePagador::Clinica) {
            return new Pagador($tipo);
        }

        $clinica = $input->clinicaId === null ? null : $this->pagadores->clinica($input->clinicaId);
        if ($clinica === null || ! $clinica->ativa) {
            throw ReferenciaInvalida::clinica();
        }

        return Pagador::clinica($clinica->id);
    }

    /** @return list<ItemDoPedido> o preço de cada exame já com o desconto da clínica, quando a clínica paga */
    private function itens(AbrirPedidoInput $input, Pagador $pagador): array
    {
        $desconto = $pagador->clinicaId === null ? 0 : ($this->pagadores->clinica($pagador->clinicaId)->descontoPercentual ?? 0);

        $itens = [];
        foreach ($input->exameIds as $exameId) {
            $exame = $this->catalogo->ativo($exameId) ?? throw ReferenciaInvalida::exame();
            $itens[] = ItemDoPedido::cotar($this->pedidos->nextItemIdentity(), $exame, $desconto);
        }

        return $itens;
    }
}
