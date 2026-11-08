<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Referencia;

use Vidra\Catalogo\Domain\Referencia\PerfilDeReferencia;
use Vidra\Catalogo\Domain\Referencia\PerfisDeReferencia;

final class PerfisVeterinarios implements PerfisDeReferencia
{
    public function todos(): array
    {
        return [
            new PerfilDeReferencia('cao.filhote', 'Cão filhote (até 1 ano)', 'Cão'),
            new PerfilDeReferencia('cao.adulto', 'Cão adulto (1 a 7 anos)', 'Cão'),
            new PerfilDeReferencia('cao.idoso', 'Cão idoso (7 anos ou mais)', 'Cão'),
            new PerfilDeReferencia('gato.filhote', 'Gato filhote (até 1 ano)', 'Gato'),
            new PerfilDeReferencia('gato.adulto', 'Gato adulto (1 a 10 anos)', 'Gato'),
            new PerfilDeReferencia('gato.idoso', 'Gato idoso (10 anos ou mais)', 'Gato'),
            new PerfilDeReferencia('equino.potro', 'Potro (até 2 anos)', 'Equino'),
            new PerfilDeReferencia('equino.adulto', 'Equino adulto (2 a 15 anos)', 'Equino'),
            new PerfilDeReferencia('equino.idoso', 'Equino idoso (15 anos ou mais)', 'Equino'),
            new PerfilDeReferencia('ave', 'Ave', 'Ave'),
        ];
    }

    public function existe(string $chave): bool
    {
        return in_array($chave, array_map(fn (PerfilDeReferencia $perfil): string => $perfil->chave, $this->todos()), true);
    }
}
