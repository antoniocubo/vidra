<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\DetalharVinculo;

use Vidra\Veterinaria\Application\ListarVeterinarios\MontarVeterinariosDto;
use Vidra\Veterinaria\Application\ListarVeterinarios\VeterinarioDto;
use Vidra\Veterinaria\Domain\Vinculo\Exceptions\VinculoNaoEncontrado;
use Vidra\Veterinaria\Domain\Vinculo\VinculoId;
use Vidra\Veterinaria\Domain\Vinculo\VinculoRepository;

final readonly class DetalharVinculo
{
    public function __construct(private VinculoRepository $vinculos, private MontarVeterinariosDto $montar) {}

    public function __invoke(string $vinculoId): VeterinarioDto
    {
        $vinculo = $this->vinculos->porId(new VinculoId($vinculoId)) ?? throw VinculoNaoEncontrado::para();

        return ($this->montar)([$vinculo])[0] ?? throw VinculoNaoEncontrado::para();
    }
}
