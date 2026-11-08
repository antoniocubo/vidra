<?php

declare(strict_types=1);

namespace Vidra\Bancada\Domain;

final readonly class MotorDeReferencia
{
    public function classificar(Decimal $valor, ?Faixa $faixa): Classificacao
    {
        if ($faixa === null) {
            return Classificacao::SemReferencia;
        }
        if ($faixa->criticoBaixo !== null && $valor->comparar($faixa->criticoBaixo) <= 0) {
            return Classificacao::CriticoBaixo;
        }
        if ($faixa->criticoAlto !== null && $valor->comparar($faixa->criticoAlto) >= 0) {
            return Classificacao::CriticoAlto;
        }
        if ($faixa->minimo !== null && $valor->comparar($faixa->minimo) < 0) {
            return Classificacao::Baixo;
        }
        if ($faixa->maximo !== null && $valor->comparar($faixa->maximo) > 0) {
            return Classificacao::Alto;
        }

        return Classificacao::Normal;
    }
}
