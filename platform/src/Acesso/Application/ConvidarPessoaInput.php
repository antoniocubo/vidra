<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application;

final readonly class ConvidarPessoaInput
{
    public function __construct(public string $nome, public string $email, public string $papel, public int $convidadoPor) {}
}
