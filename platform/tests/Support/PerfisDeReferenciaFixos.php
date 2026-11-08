<?php

declare(strict_types=1);

namespace Tests\Support;

use Vidra\Catalogo\Domain\Referencia\PerfilDeReferencia;
use Vidra\Catalogo\Domain\Referencia\PerfisDeReferencia;

final readonly class PerfisDeReferenciaFixos implements PerfisDeReferencia
{
    public function todos(): array
    {
        return [new PerfilDeReferencia('cao.adulto', 'Adulto', 'Cão'), new PerfilDeReferencia('gato.adulto', 'Adulto', 'Gato')];
    }

    public function existe(string $chave): bool
    {
        return in_array($chave, array_map(fn (PerfilDeReferencia $p): string => $p->chave, $this->todos()), true);
    }
}
