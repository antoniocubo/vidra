<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain\Assinatura;

/** O que o gateway devolve ao criar uma cobrança. */
final readonly class PagamentoDoGateway
{
    public function __construct(public string $id, public ?string $link, public int $valorCentavos, public string $vencimento) {}
}
