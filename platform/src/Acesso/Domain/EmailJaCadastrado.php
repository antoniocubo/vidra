<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

final class EmailJaCadastrado extends \Vidra\Shared\Domain\DomainException
{
    public static function para(\Vidra\Shared\Domain\ValueObjects\Email $email): self
    {
        return new self('Este e-mail já tem uma conta. Entre com sua senha.');
    }
}
