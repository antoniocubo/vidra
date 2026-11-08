<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\GerarEtiquetas;

use Vidra\Pedidos\Domain\Amostra\Amostra;
use Vidra\Pedidos\Domain\Amostra\AmostraRepository;
use Vidra\Pedidos\Domain\Amostra\SituacaoDaAmostra;
use Vidra\Pedidos\Domain\Pedido\Exceptions\PedidoNaoEncontrado;
use Vidra\Pedidos\Domain\Pedido\PedidoId;
use Vidra\Pedidos\Domain\Pedido\PedidoRepository;
use Vidra\Pedidos\Domain\Portas\GeradorDeCodigoDeBarras;
use Vidra\Pedidos\Domain\Portas\Pacientes;

final readonly class GerarEtiquetas
{
    public function __construct(private PedidoRepository $pedidos, private AmostraRepository $amostras, private Pacientes $pacientes, private GeradorDeCodigoDeBarras $barras) {}

    /** @return list<EtiquetaDto> uma por amostra; as rejeitadas não voltam à bancada, então não ganham etiqueta */
    public function __invoke(string $pedidoId): array
    {
        $pedido = $this->pedidos->porId(new PedidoId($pedidoId)) ?? throw PedidoNaoEncontrado::para();
        $paciente = $this->pacientes->resumo($pedido->pacienteId);
        $validas = array_filter($this->amostras->doPedido($pedido->id), fn (Amostra $a): bool => $a->situacao() !== SituacaoDaAmostra::Rejeitada);

        return array_values(array_map(fn (Amostra $a): EtiquetaDto => new EtiquetaDto(
            $a->codigo->value, $this->barras->svg($a->codigo->value), $paciente->nome ?? '', $paciente->especieCurta ?? '', $a->material, (string) $pedido->protocolo,
        ), $validas));
    }
}
