<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Acesso;

use Illuminate\Support\Str;
use Vidra\Laudos\Application\ConsultarEntrega;
use Vidra\Laudos\Domain\PedidoNaoEncontrado;
use Vidra\Laudos\Infrastructure\AcessoPublicoDoLaudo;
use Vidra\Laudos\Infrastructure\Persistence\AcessoModel;
use Vidra\Laudos\Infrastructure\Persistence\LaudoModel;
use Vidra\Veterinaria\Domain\Acesso\DownloadVeterinario;

final readonly class DownloadVeterinarioEloquent implements DownloadVeterinario
{
    public function __construct(private ConsultarEntrega $entrega) {}

    public function baixar(string $pedido, string $ip, string $agente): array
    {
        $laudo = LaudoModel::query()->where('pedido_id', $pedido)->first() ?? throw new PedidoNaoEncontrado;
        $pdf = $this->entrega->pdf($pedido);
        AcessoModel::query()->create(['id' => (string) Str::uuid7(), 'laudo_id' => $laudo->id, 'quando' => now(), 'ip' => AcessoPublicoDoLaudo::ipReduzido($ip), 'user_agent' => mb_substr($agente, 0, 200)]);

        return ['conteudo' => $pdf->bytes, 'nome' => $pdf->nome];
    }
}
