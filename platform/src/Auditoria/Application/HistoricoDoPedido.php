<?php

declare(strict_types=1);

namespace Vidra\Auditoria\Application;

use Vidra\Auditoria\Domain\TrilhaDeAuditoria;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class HistoricoDoPedido
{
    public function __construct(private TrilhaDeAuditoria $trilha, private LaboratorioAtual $laboratorio) {}

    /** @return list<array{id:string,em:string,quem:string,detalhe:string}> */
    public function __invoke(string $pedidoId): array
    {
        $this->laboratorio->id();

        return array_map(RegistroDto::de(...), $this->trilha->doPedido($pedidoId));
    }
}
