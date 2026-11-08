<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Domain\Exame;

use Vidra\Catalogo\Domain\Exame\Exceptions\FaixaInvalida;

final readonly class FaixaDeReferencia
{
    public function __construct(public string $perfil, public ?Decimal $minimo, public ?Decimal $maximo, public ?Decimal $criticoBaixo = null, public ?Decimal $criticoAlto = null)
    {
        if ($minimo === null && $maximo === null) {
            throw FaixaInvalida::para('minimo', 'Informe pelo menos um limite de referência.');
        }
        if ($minimo !== null && $maximo !== null && $minimo->comparar($maximo) > 0) {
            throw FaixaInvalida::para('maximo', 'O máximo deve ser maior ou igual ao mínimo.');
        }
        if ($criticoBaixo !== null && $minimo !== null && $criticoBaixo->comparar($minimo) >= 0) {
            throw FaixaInvalida::para('criticoBaixo', 'O crítico baixo deve ser menor que o mínimo.');
        }
        if ($criticoAlto !== null && $maximo !== null && $criticoAlto->comparar($maximo) <= 0) {
            throw FaixaInvalida::para('criticoAlto', 'O crítico alto deve ser maior que o máximo.');
        }
    }
}
