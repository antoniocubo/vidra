<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Vinculo;

use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioId;

final readonly class Vinculo
{
    private function __construct(
        public VinculoId $id,
        public string $laboratorioId,
        public VeterinarioId $veterinarioId,
        public QuemPaga $quemPaga,
        public Telefone $telefoneCritico,
        public bool $vePedidosDosColegas,
        public RecebeLaudo $recebeLaudo,
    ) {}

    public static function registrar(VinculoId $id, string $laboratorioId, VeterinarioId $veterinarioId, QuemPaga $quemPaga, Telefone $telefoneCritico, bool $vePedidosDosColegas, RecebeLaudo $recebeLaudo): self
    {
        return new self($id, $laboratorioId, $veterinarioId, $quemPaga, $telefoneCritico, $vePedidosDosColegas, $recebeLaudo);
    }

    public static function restaurar(VinculoId $id, string $laboratorioId, VeterinarioId $veterinarioId, QuemPaga $quemPaga, Telefone $telefoneCritico, bool $vePedidosDosColegas, RecebeLaudo $recebeLaudo): self
    {
        return new self($id, $laboratorioId, $veterinarioId, $quemPaga, $telefoneCritico, $vePedidosDosColegas, $recebeLaudo);
    }
}
