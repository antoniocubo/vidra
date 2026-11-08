<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

interface FonteDoLaudo
{
    public function montar(Laudo $laudo): ConteudoDoLaudo;
}
