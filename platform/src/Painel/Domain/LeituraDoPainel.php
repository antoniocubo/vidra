<?php

declare(strict_types=1);

namespace Vidra\Painel\Domain;

use DateTimeImmutable;

interface LeituraDoPainel
{
    /** @return array<string, int> */
    public function indicadores(DateTimeImmutable $inicio, DateTimeImmutable $fim): array;

    /** @return list<array{id:string, pedidoId:string, protocolo:int, tipo:string, detalhe:string, destino:string, criadoEm:string, prazo:int}> */
    public function atencao(DateTimeImmutable $agora): array;

    /** @return list<array{id:string, protocolo:int, situacao:string}> */
    public function andamento(DateTimeImmutable $inicio, DateTimeImmutable $fim): array;
}
