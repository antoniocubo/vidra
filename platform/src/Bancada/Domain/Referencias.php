<?php

declare(strict_types=1);

namespace Vidra\Bancada\Domain;

interface Referencias
{
    public function descricaoDoPerfil(string $exameId, ?string $perfil): ?string;

    /**
     * @return list<Analito> */
    public function doExame(string $exameId, ?string $perfil): array;
}
