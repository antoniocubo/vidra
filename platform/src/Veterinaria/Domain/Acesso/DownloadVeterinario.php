<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Acesso;

interface DownloadVeterinario
{
    /** @return array{conteudo:string,nome:string} */
    public function baixar(string $pedido, string $ip, string $agente): array;
}
