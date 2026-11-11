<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\PedidosDoVeterinario;

final readonly class PedidoDoVeterinarioDto
{
    /** @param list<string> $exames */
    public function __construct(
        public string $id, public string $animal, public string $tutor, public array $exames,
        public string $situacao, public string $situacaoRotulo, public bool $critico,
        public bool $pdfPronto, public ?string $whatsapp,
    ) {}
}
