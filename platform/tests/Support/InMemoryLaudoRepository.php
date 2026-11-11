<?php

declare(strict_types=1);

namespace Tests\Support;

use Vidra\Laudos\Domain\AvisoDeValorCritico;
use Vidra\Laudos\Domain\CodigoDeAcesso;
use Vidra\Laudos\Domain\Laudo;
use Vidra\Laudos\Domain\LaudoRepository;
use Vidra\Laudos\Domain\VersaoDoLaudo;

final class InMemoryLaudoRepository implements LaudoRepository
{
    /** @var array<string,Laudo> */
    public array $laudos = [];

    /** @var array<string,array<int,VersaoDoLaudo>> */
    public array $historico = [];

    /** @var list<AvisoDeValorCritico> */
    private array $avisos = [];

    public function nextIdentity(): string
    {
        return 'laudo-'.(count($this->laudos) + 1);
    }

    public function proximoNumero(): int
    {
        return count($this->laudos) + 1;
    }

    public function codigoExiste(CodigoDeAcesso $codigo): bool
    {
        return array_any($this->laudos, fn (Laudo $l): bool => $l->codigoAcesso === $codigo);
    }

    public function save(Laudo $laudo): void
    {
        $this->laudos[$laudo->pedidoId] = $laudo;
        $this->historico[$laudo->id][$laudo->versao] ??= new VersaoDoLaudo($laudo->versao, $laudo->liberadoEm, $laudo->usuarioNome, $laudo->retificacao?->motivo, null);
    }

    public function doPedido(string $pedidoId): ?Laudo
    {
        return $this->laudos[$pedidoId] ?? null;
    }

    public function versoes(string $laudoId): array
    {
        return array_values($this->historico[$laudoId] ?? []);
    }

    public function saveAviso(AvisoDeValorCritico $aviso): void
    {
        $this->avisos[] = $aviso;
    }

    public function avisos(string $pedidoId): array
    {
        return array_values(array_filter($this->avisos, fn (AvisoDeValorCritico $a): bool => $a->pedidoId === $pedidoId));
    }
}
