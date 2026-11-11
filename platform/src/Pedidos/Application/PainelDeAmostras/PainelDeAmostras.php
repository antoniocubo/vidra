<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\PainelDeAmostras;

use Vidra\Pedidos\Application\DiaLocal;
use Vidra\Pedidos\Application\Formato;
use Vidra\Pedidos\Domain\Amostra\Amostra;
use Vidra\Pedidos\Domain\Amostra\AmostraId;
use Vidra\Pedidos\Domain\Amostra\AmostraRepository;
use Vidra\Pedidos\Domain\Amostra\Exceptions\AmostraNaoEncontrada;
use Vidra\Pedidos\Domain\Amostra\MensagemDeRecoleta;
use Vidra\Pedidos\Domain\Amostra\MotivoDeRejeicao;
use Vidra\Pedidos\Domain\Pedido\ItemDoPedido;
use Vidra\Pedidos\Domain\Pedido\Pedido;
use Vidra\Pedidos\Domain\Pedido\PedidoRepository;
use Vidra\Pedidos\Domain\Pedido\TipoDePagador;
use Vidra\Pedidos\Domain\Portas\PacienteDoPedido;
use Vidra\Pedidos\Domain\Portas\Pacientes;
use Vidra\Pedidos\Domain\Portas\Pagadores;
use Vidra\Pedidos\Domain\Portas\SolicitanteDoPedido;
use Vidra\Pedidos\Domain\Portas\Solicitantes;
use Vidra\Pedidos\Domain\Relogio;

final readonly class PainelDeAmostras
{
    private const int LIMITE_AGUARDANDO = 100;

    public function __construct(
        private AmostraRepository $amostras,
        private PedidoRepository $pedidos,
        private Pacientes $pacientes,
        private Solicitantes $solicitantes,
        private Pagadores $pagadores,
        private Relogio $relogio,
    ) {}

    /** @throws AmostraNaoEncontrada quando a amostra pedida não existe neste laboratório */
    public function __invoke(?string $data = null, ?string $amostraId = null): PainelDeAmostrasDto
    {
        $dia = DiaLocal::de($data, $this->relogio->agora());
        $recebidas = $this->amostras->recebidasEntre($dia->inicio, $dia->fim);
        $aguardando = array_values(array_filter($this->amostras->aguardandoChegada(self::LIMITE_AGUARDANDO), fn (Amostra $a): bool => $a->itemIds !== []));
        $selecionada = $amostraId === null ? null : ($this->amostras->porId(new AmostraId($amostraId)) ?? throw AmostraNaoEncontrada::para());

        if ($selecionada !== null && $selecionada->itemIds === []) {
            $selecionada = null;
        }

        $todas = [...$recebidas, ...$aguardando, ...($selecionada === null ? [] : [$selecionada])];
        $pedidos = $this->pedidos->porIds(array_values(array_unique(array_map(fn (Amostra $a): string => $a->pedidoId->value, $todas))));
        $pacientes = $this->pacientes->resumos(array_values(array_unique(array_map(fn (Pedido $p): string => $p->pacienteId, $pedidos))));
        $clinicas = $this->pagadores->clinicas(array_values(array_filter(array_map(fn (Pedido $p): ?string => $p->pagador->clinicaId, $pedidos))));
        $solicitantes = $this->solicitantes->resumos(array_values(array_unique(array_map(fn (Pedido $p): string => $p->solicitanteId, $pedidos))));

        $linha = function (Amostra $a) use ($pedidos, $pacientes, $clinicas, $solicitantes): AmostraLinhaDto {
            $pedido = $pedidos[$a->pedidoId->value] ?? null;
            $paciente = $pedido === null ? null : ($pacientes[$pedido->pacienteId] ?? null);
            $pagador = $pedido === null ? '' : match ($pedido->pagador->tipo) {
                TipoDePagador::Clinica => $clinicas[$pedido->pagador->clinicaId ?? '']->nome ?? 'Clínica removida',
                TipoDePagador::Solicitante => ($solicitantes[$pedido->solicitanteId]->nome ?? 'Solicitante'),
                TipoDePagador::Responsavel => 'Balcão',
            };

            return new AmostraLinhaDto(
                $a->id->value, $a->codigo->value, $paciente->nome ?? '', $paciente->especieCurta ?? '', $a->material, $pagador,
                $a->situacao()->value, $a->motivo()?->rotulo(), $a->recebidaEm() === null ? null : Formato::hora($a->recebidaEm()),
            );
        };

        return new PainelDeAmostrasDto(
            $dia->data,
            array_map($linha, $recebidas),
            array_map($linha, $aguardando),
            $selecionada === null ? null : $this->selecionada($selecionada, $linha($selecionada), $pedidos[$selecionada->pedidoId->value] ?? null, $pacientes, $solicitantes),
        );
    }

    /**
     * @param  array<string, PacienteDoPedido>  $pacientes
     * @param  array<string, SolicitanteDoPedido>  $solicitantes
     */
    private function selecionada(Amostra $amostra, AmostraLinhaDto $linha, ?Pedido $pedido, array $pacientes, array $solicitantes): AmostraSelecionadaDto
    {
        $paciente = $pedido === null ? null : ($pacientes[$pedido->pacienteId] ?? null);
        $exames = $pedido === null ? [] : array_values(array_map(
            fn (ItemDoPedido $i): string => $i->nome,
            array_filter($pedido->itens(), fn (ItemDoPedido $i): bool => in_array($i->id->value, $amostra->itemIds, true)),
        ));

        $mensagens = [];
        foreach (MotivoDeRejeicao::cases() as $motivo) {
            $detalhe = $motivo === MotivoDeRejeicao::Outro ? '{texto}' : null;
            $mensagens[$motivo->value] = MensagemDeRecoleta::montar($amostra->codigo->value, $paciente->nome ?? '', $motivo, $detalhe, $amostra->material, $exames);
        }

        return new AmostraSelecionadaDto(
            $linha,
            $amostra->pedidoId->value,
            $pedido === null ? '' : (string) $pedido->protocolo,
            $paciente->descricao ?? '',
            $pedido === null ? '' : ($solicitantes[$pedido->solicitanteId]->nome ?? ''),
            $exames,
            $mensagens,
            $amostra->motivo()?->value,
            $amostra->motivoTexto(),
            $amostra->ressalva(),
            $amostra->recoleta()?->value,
        );
    }
}
