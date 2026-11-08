<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\AbrirPedido;

final readonly class AbrirPedidoInput
{
    /** @param list<string> $exameIds */
    public function __construct(
        public string $pacienteId,
        public string $solicitanteId,
        public string $pagador,
        public ?string $clinicaId,
        public string $prioridade,
        public array $exameIds,
        public ?string $observacao,
        public int $criadoPor,
    ) {}
}
