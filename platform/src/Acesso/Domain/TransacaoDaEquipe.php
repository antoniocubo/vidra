<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

use Closure;

interface TransacaoDaEquipe
{
    /** Serializa alterações da equipe e dos convites do mesmo laboratório.
     * @template T
     *
     * @param  Closure(): T  $acao
     * @return T
     */
    public function executar(string $laboratorioId, Closure $acao): mixed;
}
