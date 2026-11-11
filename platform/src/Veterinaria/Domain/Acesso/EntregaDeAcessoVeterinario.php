<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Acesso;

interface EntregaDeAcessoVeterinario
{
    public function enviar(string $email, string $nome, string $laboratorio, string $token): void;
}
