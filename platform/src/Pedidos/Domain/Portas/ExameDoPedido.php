<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Portas;

final readonly class ExameDoPedido
{
    public function __construct(
        public string $id,
        public string $codigo,
        public string $nome,
        public string $setor,
        public string $material,
        public int $precoCentavos,
    ) {}
}
