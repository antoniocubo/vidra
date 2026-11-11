<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

final readonly class MembroDaEquipe
{
    public function __construct(public int $id, public string $nome, public string $email, public Papel $papel, public bool $eRt) {}
}
