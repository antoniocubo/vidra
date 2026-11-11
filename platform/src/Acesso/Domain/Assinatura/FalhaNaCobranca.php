<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain\Assinatura;

use Vidra\Shared\Domain\DomainException;

final class FalhaNaCobranca extends DomainException
{
    public static function indisponivel(): self
    {
        return new self('Não conseguimos falar com o serviço de cobrança agora. Tente de novo em alguns minutos.');
    }

    public static function recusada(string $motivo): self
    {
        return new self('O serviço de cobrança recusou o pedido: '.$motivo);
    }
}
