<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Domain\Exame\Exceptions;

final class PerfilDesconhecido extends \Vidra\Shared\Domain\DomainException
{
    public string $campo = 'perfil';

    public static function para(string $perfil, string $campo = 'perfil'): self
    {
        $erro = new self("O perfil {$perfil} não está disponível.");
        $erro->campo = $campo;

        return $erro;
    }
}
