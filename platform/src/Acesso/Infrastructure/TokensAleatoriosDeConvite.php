<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure;

use Illuminate\Support\Str;
use Vidra\Acesso\Domain\TokensDeConvite;

final class TokensAleatoriosDeConvite implements TokensDeConvite
{
    public function gerar(): string
    {
        return Str::random(48);
    }
}
