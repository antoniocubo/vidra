<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain\Assinatura;

interface AvisosDeAssinatura
{
    /** @param list<string> $emails */
    public function enviar(array $emails, string $laboratorio, SituacaoDaAssinatura $situacao, int $diasRestantes, ?string $link): void;
}
