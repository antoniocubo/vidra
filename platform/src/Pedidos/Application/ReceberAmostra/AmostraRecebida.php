<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\ReceberAmostra;

final readonly class AmostraRecebida
{
    public function __construct(public string $amostraId, public string $codigo, public string $pacienteNome, public string $material) {}

    /** Texto do aviso depois do bipe: "A-0412-1 recebida · Thor · Soro". */
    public function mensagem(): string
    {
        return "{$this->codigo} recebida · {$this->pacienteNome} · {$this->material}";
    }
}
