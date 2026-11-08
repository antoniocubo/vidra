<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Domain\Exame;

use Vidra\Shared\Domain\ValueObjects\InvalidValue;

final readonly class CodigoDoExame
{
    public string $value;

    public function __construct(string $value)
    {
        $this->value = strtoupper(trim($value));
        if (! preg_match('/^[A-Z0-9-]{2,12}$/D', $this->value)) {
            throw InvalidValue::for('codigo', 'Use de 2 a 12 letras, números ou hífen.');
        }
    }
}
