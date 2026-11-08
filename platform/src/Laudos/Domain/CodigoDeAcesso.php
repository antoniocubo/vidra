<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

use Vidra\Shared\Domain\ValueObjects\InvalidValue;

final readonly class CodigoDeAcesso
{
    public const string ALFABETO = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(public string $value)
    {
        if (! preg_match('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{4}-[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{4}$/D', $value)) {
            throw InvalidValue::for('codigo', 'Código de acesso inválido.');
        }
    }

    public static function gerar(): self
    {
        $v = '';
        for ($i = 0; $i < 8; $i++) {
            $v .= self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)];
        }

        return new self(substr($v, 0, 4).'-'.substr($v, 4));
    }
}
