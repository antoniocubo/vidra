<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Amostra;

use Stringable;
use Vidra\Pedidos\Domain\Pedido\Protocolo;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;

/** O que está na etiqueta do tubo: A-0412-1 (protocolo 412, primeira amostra). */
final readonly class CodigoDaAmostra implements Stringable
{
    private const string FORMATO = '/^A-(\d{4,})-(\d+)$/D';

    public string $value;

    public function __construct(string $value)
    {
        $this->value = strtoupper(trim($value));
        if (preg_match(self::FORMATO, $this->value) !== 1) {
            throw InvalidValue::for('código da amostra', 'use o formato A-0412-1');
        }
    }

    public static function de(Protocolo $protocolo, int $sequencia): self
    {
        return new self('A-'.str_pad((string) $protocolo->numero, 4, '0', STR_PAD_LEFT).'-'.$sequencia);
    }

    /** O que o leitor digitou pode não ser um código nosso: nesse caso devolve null. */
    public static function tentarLer(string $texto): ?self
    {
        $texto = strtoupper(trim($texto));

        return preg_match(self::FORMATO, $texto) === 1 ? new self($texto) : null;
    }

    public function sequencia(): int
    {
        preg_match(self::FORMATO, $this->value, $partes);

        return (int) ($partes[2] ?? 0);
    }

    public function equals(self $outro): bool
    {
        return $this->value === $outro->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
