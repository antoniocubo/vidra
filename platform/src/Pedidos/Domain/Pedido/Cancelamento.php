<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Pedido;

use DateTimeImmutable;
use DateTimeZone;
use Vidra\Pedidos\Domain\Pedido\Exceptions\PedidoInvalido;

/** Quem cancelou um exame, quando e por quê. O motivo é obrigatório. */
final readonly class Cancelamento
{
    public string $motivo;

    public DateTimeImmutable $em;

    public function __construct(string $motivo, public int $por, DateTimeImmutable $em)
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw PedidoInvalido::semMotivo();
        }
        if (mb_strlen($motivo) > 500) {
            throw PedidoInvalido::motivoLongo();
        }

        $this->motivo = $motivo;
        $this->em = $em->setTimezone(new DateTimeZone('UTC'));
    }
}
