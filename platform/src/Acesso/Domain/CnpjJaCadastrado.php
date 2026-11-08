<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

use Vidra\Shared\Domain\ValueObjects\Cnpj;

final class CnpjJaCadastrado extends \Vidra\Shared\Domain\DomainException
{
    public static function para(Cnpj $cnpj): self
    {
        return new self('Já existe um laboratório com este CNPJ.');
    }
}
