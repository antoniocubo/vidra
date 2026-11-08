<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Domain\Exame;

use Vidra\Shared\Domain\ValueObjects\InvalidValue;

final readonly class Decimal
{
    public string $value;

    public function __construct(string $value)
    {
        if (! preg_match('/^-?\d+(?:\.\d{1,4})?$/D', $value)) {
            throw InvalidValue::for('decimal', 'Use um decimal com até quatro casas.');
        }
        $negativo = str_starts_with($value, '-');
        [$inteiro, $fracao] = array_pad(explode('.', ltrim($value, '-')), 2, '');
        $inteiro = ltrim($inteiro, '0') ?: '0';
        $fracao = rtrim($fracao, '0');
        if (strlen($inteiro) > 10) {
            throw InvalidValue::for('decimal', 'Use até dez algarismos inteiros.');
        }
        $normal = $inteiro.($fracao !== '' ? '.'.$fracao : '');
        $this->value = ($negativo && $normal !== '0' ? '-' : '').$normal;
    }

    public function comparar(self $outro): int
    {
        $aNeg = str_starts_with($this->value, '-');
        $bNeg = str_starts_with($outro->value, '-');
        if ($aNeg !== $bNeg) {
            return $aNeg ? -1 : 1;
        }
        $a = explode('.', ltrim($this->value, '-'));
        $b = explode('.', ltrim($outro->value, '-'));
        $resultado = strlen($a[0]) <=> strlen($b[0]);
        if ($resultado === 0) {
            $resultado = strcmp($a[0].str_pad($a[1] ?? '', 4, '0'), $b[0].str_pad($b[1] ?? '', 4, '0')) <=> 0;
        }

        return $aNeg ? -$resultado : $resultado;
    }
}
