<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Tutor\Exceptions;

use Vidra\Shared\Domain\DomainException;

final class TutorSemEmail extends DomainException
{
    public static function para(): self
    {
        return new self('Informe o e-mail do tutor antes de autorizar o envio de laudos.');
    }
}
