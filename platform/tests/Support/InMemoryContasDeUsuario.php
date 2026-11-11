<?php

declare(strict_types=1);

namespace Tests\Support;

use SensitiveParameter;
use Vidra\Acesso\Domain\ContasDeUsuario;

final class InMemoryContasDeUsuario implements ContasDeUsuario
{
    /** @var array<string,int> */
    public array $dados = [];

    public function existe(string $email): bool
    {
        return isset($this->dados[strtolower($email)]);
    }

    public function criar(string $nome, string $email, #[SensitiveParameter] string $senha): int
    {
        return $this->dados[$email] = count($this->dados) + 10;
    }
}
