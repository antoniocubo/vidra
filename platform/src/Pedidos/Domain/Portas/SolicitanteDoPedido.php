<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Portas;

final readonly class SolicitanteDoPedido
{
    public function __construct(
        public string $id,
        /** Como sai no laudo: "Dr. Rafael Lima". */
        public string $nome,
        /** "CRMV-SP 41208". */
        public string $registro,
        public string $email,
        public string $telefoneCritico,
        /** 'clinica', 'solicitante' ou 'responsavel': quem costuma pagar os pedidos dele. */
        public string $pagadorPadrao,
    ) {}
}
