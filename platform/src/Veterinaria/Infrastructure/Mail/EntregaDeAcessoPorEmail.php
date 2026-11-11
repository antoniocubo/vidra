<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Mail;

use Illuminate\Support\Facades\Mail;
use Vidra\Veterinaria\Domain\Acesso\EntregaDeAcessoVeterinario;

final class EntregaDeAcessoPorEmail implements EntregaDeAcessoVeterinario
{
    public function enviar(string $email, string $nome, string $laboratorio, string $token): void
    {
        Mail::to($email)->send(new AcessoDoVeterinarioMail($nome, $laboratorio, $token));
    }
}
