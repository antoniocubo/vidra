<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Portas;

interface Vocabulario
{
    public function textos(): TextosDaLinha;
}
