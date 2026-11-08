<?php

declare(strict_types=1);

namespace Tests\Support;

use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class LaboratorioAtualFixo implements LaboratorioAtual
{
    public function __construct(private string $laboratorioId = '019a5d70-0000-7000-8000-000000000001') {}

    public function id(): string
    {
        return $this->laboratorioId;
    }

    public function definido(): bool
    {
        return true;
    }
}
