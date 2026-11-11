<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application\Assinatura;

final readonly class EventoDoAsaas
{
    public function __construct(
        public string $nome,
        public string $pagamentoId,
        public string $clienteId,
        public int $valorCentavos,
        /** AAAA-MM-DD */
        public string $vencimento,
        public ?string $link,
    ) {}
}
