<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\PainelDeAmostras;

final readonly class AmostraLinhaDto
{
    public function __construct(
        public string $id,
        public string $codigo,
        public string $pacienteNome,
        public string $especieCurta,
        public string $material,
        public string $pagador,
        public string $situacao,
        public ?string $motivo,
        public ?string $recebidaEm,
    ) {}
}
