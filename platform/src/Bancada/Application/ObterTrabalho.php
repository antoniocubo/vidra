<?php

declare(strict_types=1);

namespace Vidra\Bancada\Application;

use Vidra\Bancada\Domain\ItemNaoEncontrado;
use Vidra\Bancada\Domain\Trabalho;
use Vidra\Bancada\Domain\Trabalhos;

final readonly class ObterTrabalho
{
    public function __construct(private Trabalhos $trabalhos) {}

    public function __invoke(string $id): Trabalho
    {
        foreach ($this->trabalhos->todos() as $i) {
            if ($i->id === $id) {
                return $i;
            }
        }
        throw new ItemNaoEncontrado;
    }
}
