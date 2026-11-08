<?php

declare(strict_types=1);

namespace Vidra\Painel\Infrastructure;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/** Projeção de leitura: aplica o mesmo scope a cada fonte, inclusive subconsultas. */
final class RegistroDoPainel extends Model
{
    use PertenceAoLaboratorio;

    public static function tabela(string $tabela): Builder
    {
        $nome = str_contains($tabela, ' as ') ? explode(' as ', $tabela)[1] : $tabela;

        return (new self)->setTable($nome)->newQuery()->from($tabela)->toBase();
    }
}
