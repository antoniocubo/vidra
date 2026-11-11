<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

use DateTimeImmutable;

final readonly class VersaoDoLaudo
{
    public function __construct(public int $versao, public DateTimeImmutable $liberadoEm, public string $usuarioNome, public ?string $motivo, public ?string $pdfCaminho) {}
}
