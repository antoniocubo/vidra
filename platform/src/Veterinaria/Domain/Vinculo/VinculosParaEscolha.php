<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Vinculo;

interface VinculosParaEscolha
{
    /** @return array<string, string> id do vínculo => nome de exibição */
    public function nomes(): array;
}
