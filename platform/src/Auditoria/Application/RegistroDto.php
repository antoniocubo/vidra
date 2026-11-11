<?php

declare(strict_types=1);

namespace Vidra\Auditoria\Application;

use Vidra\Auditoria\Domain\RegistroDeAuditoria;

final readonly class RegistroDto
{
    /** @return array{id:string,em:string,quem:string,detalhe:string} */
    public static function de(RegistroDeAuditoria $r): array
    {
        return ['id' => $r->id, 'em' => $r->em->format('c'), 'quem' => $r->usuarioNome, 'detalhe' => $r->detalhe];
    }
}
