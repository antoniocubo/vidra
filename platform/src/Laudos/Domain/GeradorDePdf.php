<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

interface GeradorDePdf
{
    public function deHtml(string $html): string;
}
