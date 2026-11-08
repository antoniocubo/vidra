<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Domain\Exame\Exceptions;

final class ErroNaFaixa extends \Vidra\Shared\Domain\DomainException
{
    public function __construct(public readonly string $campo, \Vidra\Shared\Domain\DomainException $causa)
    {
        parent::__construct($causa->getMessage(), 0, $causa);
    }
}
