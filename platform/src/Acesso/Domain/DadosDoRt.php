<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

interface DadosDoRt
{
    /** @return array{nome:string,registro:string} */
    public function doLaboratorio(string $id): array;
}
