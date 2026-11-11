<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

final class JaFazParteDaEquipe extends \Vidra\Shared\Domain\DomainException
{
    public static function para(): self
    {
        return new self('Esta pessoa já faz parte da equipe deste laboratório.');
    }
}
