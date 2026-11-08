<?php

declare(strict_types=1);

namespace Tests\Support;

use Closure;
use Vidra\Shared\Domain\Transacao;

final readonly class TransacaoEmMemoria implements Transacao
{
    public function executar(Closure $operacao): mixed
    {
        return $operacao();
    }
}
