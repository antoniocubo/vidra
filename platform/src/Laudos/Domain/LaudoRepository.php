<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

interface LaudoRepository
{
    /** @return list<VersaoDoLaudo> */
    public function versoes(string $laudoId): array;

    public function nextIdentity(): string;

    public function proximoNumero(): int;

    public function codigoExiste(CodigoDeAcesso $codigo): bool;

    public function save(Laudo $laudo): void;

    public function doPedido(string $pedidoId): ?Laudo;

    public function saveAviso(AvisoDeValorCritico $aviso): void;

    /**
     * @return list<AvisoDeValorCritico> */
    public function avisos(string $pedidoId): array;
}
