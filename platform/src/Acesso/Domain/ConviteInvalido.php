<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

final class ConviteInvalido extends \Vidra\Shared\Domain\DomainException
{
    public static function para(): self
    {
        return new self('Este convite expirou, foi cancelado ou já foi aceito. Peça um novo convite ao administrador.');
    }
}
