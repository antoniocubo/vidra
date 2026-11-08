<?php

declare(strict_types=1);

namespace Vidra\Shared\Domain\Tenancy;

use LogicException;

/**
 * Erro de programação, não de negócio: uma consulta a dado de laboratório rodou
 * fora de um laboratório. Falhar aqui é melhor do que devolver dados de todos.
 */
final class LaboratorioNaoDefinido extends LogicException
{
    public static function naConsulta(): self
    {
        return new self('Nenhum laboratório definido para esta operação.');
    }
}
