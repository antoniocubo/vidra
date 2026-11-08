<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\ListarPedidos;

final readonly class PedidoResumoDto
{
    /** @param list<string> $exames códigos dos exames */
    public function __construct(
        public string $id,
        public string $protocolo,
        public string $pacienteNome,
        public string $especieCurta,
        public string $solicitanteNome,
        public array $exames,
        public string $situacao,
        public string $situacaoRotulo,
        public bool $urgente,
        public string $criadoEm,
    ) {}
}
