<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

use SensitiveParameter;
use Vidra\Shared\Domain\ValueObjects\Email;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Shared\Domain\ValueObjects\Telefone;

final readonly class Administrador
{
    public function __construct(public string $nome, public Email $email, public Telefone $celular, #[SensitiveParameter] public string $senha, public bool $eRt)
    {
        if (mb_strlen(trim($nome)) < 3) {
            throw InvalidValue::for('nome', 'informe seu nome completo');
        }
        if (mb_strlen($senha) < 10) {
            throw InvalidValue::for('senha', 'use pelo menos 10 caracteres');
        }
    }
}
