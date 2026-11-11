<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

use SensitiveParameter;

interface ContasDeUsuario
{
    public function existe(string $email): bool;

    public function criar(string $nome, string $email, #[SensitiveParameter] string $senha): int;
}
