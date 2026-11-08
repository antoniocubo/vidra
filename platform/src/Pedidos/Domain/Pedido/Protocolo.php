<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Pedido;

use Stringable;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;

/** Número do pedido dentro do laboratório, exibido como P-00412. */
final readonly class Protocolo implements Stringable
{
    public function __construct(public int $numero)
    {
        if ($numero < 1) {
            throw InvalidValue::for('protocolo', 'o número começa em 1');
        }
    }

    /** Aceita "P-00412", "p00412" ou só "412"; devolve null quando não parece um protocolo. */
    public static function tentarLer(string $texto): ?self
    {
        if (preg_match('/^\s*P?-?\s*(\d{1,9})\s*$/i', $texto, $partes) !== 1 || (int) $partes[1] < 1) {
            return null;
        }

        return new self((int) $partes[1]);
    }

    public function equals(self $outro): bool
    {
        return $this->numero === $outro->numero;
    }

    public function __toString(): string
    {
        return 'P-'.str_pad((string) $this->numero, 5, '0', STR_PAD_LEFT);
    }
}
