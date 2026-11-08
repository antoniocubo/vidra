<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

final readonly class DownloadDoLaudo
{
    public function __construct(public string $bytes, public string $nome) {}
}
