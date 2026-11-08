<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

/** Um laboratório em que o usuário pode entrar, com o papel dele ali. */
final readonly class LaboratorioDoUsuario
{
    public function __construct(
        public LaboratorioId $id,
        public string $nome,
        public string $papel,
    ) {}
}
