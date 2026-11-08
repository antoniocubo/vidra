<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Str;
use Vidra\Acesso\Domain\Administrador;
use Vidra\Acesso\Domain\Laboratorio;
use Vidra\Acesso\Domain\LaboratorioId;
use Vidra\Acesso\Domain\LaboratorioRepository;
use Vidra\Shared\Domain\ValueObjects\Cnpj;
use Vidra\Shared\Domain\ValueObjects\Email;

final class InMemoryLaboratorioRepository implements LaboratorioRepository
{
    /** @var array<string, Laboratorio> */
    public array $items = [];

    /** @var array<string, Administrador> */
    public array $administradores = [];

    public function nextIdentity(): LaboratorioId
    {
        return new LaboratorioId((string) Str::uuid7());
    }

    public function existeCnpj(Cnpj $cnpj): bool
    {
        foreach ($this->items as $item) {
            if ($item->cnpj->value === $cnpj->value) {
                return true;
            }
        }

        return false;
    }

    public function existeEmail(Email $email): bool
    {
        foreach ($this->administradores as $item) {
            if ($item->email->value === $email->value) {
                return true;
            }
        }

        return false;
    }

    public function save(Laboratorio $laboratorio, Administrador $administrador): void
    {
        $this->items[$laboratorio->id->value] = $laboratorio;
        $this->administradores[$laboratorio->id->value] = $administrador;
    }
}
