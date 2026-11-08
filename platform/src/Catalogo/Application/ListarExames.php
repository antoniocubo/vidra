<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Application;

use Vidra\Catalogo\Domain\Exame\Exame;
use Vidra\Catalogo\Domain\Exame\ExameRepository;
use Vidra\Catalogo\Domain\Exame\Setor;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class ListarExames
{
    public function __construct(private ExameRepository $exames, private LaboratorioAtual $laboratorio) {}

    /** @return list<ExameDto> */
    public function __invoke(string $busca = '', ?string $setor = null, bool $mostrarInativos = false): array
    {
        $this->laboratorio->id();

        return array_map(fn (Exame $e): ExameDto => new ExameDto($e), $this->exames->listar($busca, $setor !== null ? Setor::from($setor) : null, $mostrarInativos));
    }
}
