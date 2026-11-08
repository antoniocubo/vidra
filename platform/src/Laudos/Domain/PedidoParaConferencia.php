<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

final readonly class PedidoParaConferencia
{
    /**
     * @param array<string,string> $itens
     * @param list<ResultadoParaConferencia> $resultados */
    public function __construct(public string $id, public string $laboratorioId, public string $protocolo, public string $paciente, public string $solicitante, public string $telefoneCritico, public array $itens, public array $resultados) {}

    public function pronto(): bool
    {
        return $this->itens !== [] && array_filter($this->itens, fn (string $s): bool => $s !== 'resultado_digitado') === [];
    }
}
