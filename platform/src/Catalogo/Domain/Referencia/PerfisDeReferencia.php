<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Domain\Referencia;

/**
 * Os perfis de referência da linha de produto do laboratório. Implementado pela
 * linha (Veterinaria: espécie e faixa etária), nunca pelo núcleo.
 */
interface PerfisDeReferencia
{
    /** @return list<PerfilDeReferencia> na ordem de exibição */
    public function todos(): array;

    public function existe(string $chave): bool;
}
