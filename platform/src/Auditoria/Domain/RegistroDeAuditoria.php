<?php

declare(strict_types=1);

namespace Vidra\Auditoria\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class RegistroDeAuditoria
{
    public function __construct(
        public string $id, public string $laboratorioId, public AcaoAuditada $acao,
        public string $alvoTipo, public string $alvoId, public ?string $pedidoId,
        public ?int $usuarioId, public string $usuarioNome, public string $detalhe,
        public ?string $ip, public DateTimeImmutable $em,
    ) {
        if (! in_array($alvoTipo, ['pedido', 'laudo', 'equipe', 'fatura'], true) || trim($detalhe) === '') {
            throw new InvalidArgumentException('Informe um alvo válido e o detalhe do evento.');
        }
    }
}
