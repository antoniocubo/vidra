<?php

declare(strict_types=1);

namespace Vidra\Shared\Infrastructure;

use Closure;
use Illuminate\Database\ConnectionInterface;
use Vidra\Shared\Domain\Transacao;

final readonly class TransacaoEloquent implements Transacao
{
    public function __construct(private ConnectionInterface $conexao) {}

    public function executar(Closure $operacao): mixed
    {
        return $this->conexao->transaction($operacao);
    }
}
