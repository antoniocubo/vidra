<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\PedidosDoVeterinario;

use Vidra\Veterinaria\Domain\Acesso\DownloadVeterinario;
use Vidra\Veterinaria\Domain\Acesso\LaboratorioDoVeterinario;
use Vidra\Veterinaria\Domain\Acesso\PedidoInacessivel;
use Vidra\Veterinaria\Domain\Acesso\PedidosVisiveis;

final readonly class BaixarLaudoDoVeterinario
{
    public function __construct(private PedidosVisiveis $visiveis, private DownloadVeterinario $download) {}

    /** @return array{conteudo:string,nome:string} */
    public function __invoke(LaboratorioDoVeterinario $acesso, string $pedido, string $ip, string $agente): array
    {
        if (! array_key_exists($pedido, $this->visiveis->doLaboratorio($acesso))) {
            throw new PedidoInacessivel;
        }

        return $this->download->baixar($pedido, $ip, $agente);
    }
}
