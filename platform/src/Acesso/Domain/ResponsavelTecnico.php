<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

use Vidra\Shared\Domain\ValueObjects\Email;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;

final readonly class ResponsavelTecnico
{
    public function __construct(public string $nome, public RegistroProfissional $registro, public ?string $titulo, public ?Email $email)
    {
        if (mb_strlen(trim($nome)) < 3) {
            throw InvalidValue::for('rt_nome', 'informe o nome completo');
        }
    }
}
