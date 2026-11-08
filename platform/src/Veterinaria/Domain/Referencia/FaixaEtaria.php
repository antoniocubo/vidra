<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Referencia;

use DateTimeImmutable;
use Vidra\Veterinaria\Domain\Animal\Especie;

final class FaixaEtaria
{
    public static function perfil(Especie $especie, DateTimeImmutable $nascimento, DateTimeImmutable $hoje): ?string
    {
        $anos = $nascimento->diff($hoje)->y;

        return match ($especie) {
            Especie::Cao => 'cao.'.($anos < 1 ? 'filhote' : ($anos < 7 ? 'adulto' : 'idoso')),
            Especie::Gato => 'gato.'.($anos < 1 ? 'filhote' : ($anos < 10 ? 'adulto' : 'idoso')),
            Especie::Equino => 'equino.'.($anos < 2 ? 'potro' : ($anos < 15 ? 'adulto' : 'idoso')),
            Especie::Ave => 'ave', Especie::Outra => null,
        };
    }
}
