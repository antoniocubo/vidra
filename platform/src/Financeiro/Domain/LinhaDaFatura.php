<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Domain;

use DateTimeImmutable;

/** Um pedido liberado na fatura, com o preço gravado nos itens quando foi aberto. */
final readonly class LinhaDaFatura
{
    public function __construct(
        public string $pedidoId,
        public string $protocolo,
        public string $paciente,
        public DateTimeImmutable $liberadoEm,
        public string $exames,
        public int $valorCentavos,
    ) {
        if ($valorCentavos < 0) {
            throw FaturaInvalida::com('O valor de um pedido não pode ser negativo.');
        }
    }
}
