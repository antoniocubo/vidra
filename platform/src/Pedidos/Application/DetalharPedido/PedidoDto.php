<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\DetalharPedido;

final readonly class PedidoDto
{
    /**
     * @param  array{nome: string, descricao: string, especieCurta: string, responsavelNome: string, rotuloDoResponsavel: string}  $paciente
     * @param  array{nome: string, registro: string}  $solicitante
     * @param  array{tipo: string, rotulo: string, descontoPercentual: int}  $pagador
     * @param  list<array{id: string, codigo: string, nome: string, material: string, situacao: string, situacaoRotulo: string, precoCentavos: int}>  $itens
     * @param  list<array{id: string, codigo: string, material: string, situacao: string, recebidaEm: ?string}>  $amostras
     */
    public function __construct(
        public string $id,
        public string $protocolo,
        public array $paciente,
        public array $solicitante,
        public array $pagador,
        public string $prioridade,
        public string $situacao,
        public string $situacaoRotulo,
        public string $criadoEm,
        public ?string $observacao,
        public array $itens,
        public array $amostras,
        public int $totalCentavos,
        public int $etiquetas,
    ) {}
}
