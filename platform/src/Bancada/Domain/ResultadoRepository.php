<?php

declare(strict_types=1);

namespace Vidra\Bancada\Domain;

interface ResultadoRepository
{
    public function nextIdentity(): string;

    public function save(Resultado $resultado): void;

    /**
     * @return list<Resultado> */
    public function doItem(string $itemId): array;

    /**
     * @return list<Resultado> */
    public function doPedido(string $pedidoId): array;

    public function anteriorLiberado(string $pacienteId, string $analitoId, string $pedidoAtual): ?ResultadoAnterior;
}
