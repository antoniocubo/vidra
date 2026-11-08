<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

use DateTimeImmutable;

final readonly class AvisoDeValorCritico
{
    public function __construct(public string $id, public string $laboratorioId, public string $pedidoId, public string $resultadoId, public string $valorAvisado, public DateTimeImmutable $resultadoDigitadoEm, public int $avisadoPor, public DateTimeImmutable $avisadoEm, public string $destinatario, public string $telefone)
    {
        if (trim($destinatario) === '' || trim($telefone) === '') {
            throw ErroDeLiberacao::com('Informe para quem avisou e o telefone de valor crítico do solicitante.');
        }
    }
}
