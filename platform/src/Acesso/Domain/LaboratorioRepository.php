<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

use Vidra\Shared\Domain\ValueObjects\Email;

interface LaboratorioRepository
{
    public function nextIdentity(): LaboratorioId;

    public function existeCnpj(Cnpj $cnpj): bool;

    public function existeEmail(Email $email): bool;

    /** Persiste laboratório, usuário e vínculo em uma única transação. */
    public function save(Laboratorio $laboratorio, Administrador $administrador): void;
}
