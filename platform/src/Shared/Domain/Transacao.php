<?php

declare(strict_types=1);

namespace Vidra\Shared\Domain;

use Closure;

interface Transacao
{
    /**
     * @template T
     *
     * @param  Closure(): T  $operacao
     * @return T
     */
    public function executar(Closure $operacao): mixed;
}
