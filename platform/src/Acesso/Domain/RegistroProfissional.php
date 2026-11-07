<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Shared\Domain\ValueObjects\Uf;

final readonly class RegistroProfissional
{
    public string $conselho;

    public string $numero;

    public function __construct(string $conselho, string $numero, public Uf $uf)
    {
        $this->conselho = strtoupper(trim($conselho));
        $this->numero = strtoupper(trim($numero));
        if (! preg_match('/^[A-Z]{2,20}$/', $this->conselho) || ! preg_match('/^[A-Z0-9-]{1,20}$/', $this->numero)) {
            throw InvalidValue::for('rt_numero', 'informe um registro profissional válido');
        }
    }
}
