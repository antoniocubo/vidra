<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

interface TokensDeConvite
{
    public function gerar(): string;
}
