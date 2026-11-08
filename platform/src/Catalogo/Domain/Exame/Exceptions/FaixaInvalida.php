<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Domain\Exame\Exceptions;

final class FaixaInvalida extends \Vidra\Shared\Domain\DomainException
{
    public string $campo = 'minimo';

    public static function para(string $campo, string $mensagem): self
    {
        $e = new self($mensagem);
        $e->campo = $campo;

        return $e;
    }
}
