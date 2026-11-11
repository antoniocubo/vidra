<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

interface EntregaDeConvites
{
    public function enviar(Convite $convite, string $token): void;
}
