<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Application;

use Vidra\Catalogo\Domain\Exame\Exame;
use Vidra\Catalogo\Domain\Exame\ExameId;
use Vidra\Catalogo\Domain\Exame\ExameRepository;
use Vidra\Catalogo\Domain\Exame\Exceptions\ExameNaoEncontrado;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class ObterExame
{
    public function __construct(private ExameRepository $exames, private LaboratorioAtual $laboratorio) {}

    public function __invoke(string $id): Exame
    {
        $exame = $this->exames->porId(new ExameId($id));
        if ($exame === null || $exame->laboratorioId !== $this->laboratorio->id()) {
            throw new ExameNaoEncontrado;
        }

        return $exame;
    }
}
