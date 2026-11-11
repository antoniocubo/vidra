<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

final class NaoPodeAlterarASiMesmo extends \Vidra\Shared\Domain\DomainException
{
    public static function para(): self
    {
        return new self('Você não pode trocar seu próprio papel nem remover a si mesmo.');
    }
}
