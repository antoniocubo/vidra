<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Vinculo;

use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioId;

final readonly class Vinculo
{
    /** @param list<string> $clinicaIds clínicas onde o veterinário atende, nas quais o pedido pode ser cobrado */
    private function __construct(
        public VinculoId $id,
        public string $laboratorioId,
        public VeterinarioId $veterinarioId,
        public QuemPaga $quemPaga,
        public Telefone $telefoneCritico,
        public bool $vePedidosDosColegas,
        public RecebeLaudo $recebeLaudo,
        public array $clinicaIds,
    ) {}

    /** @param list<string> $clinicaIds */
    public static function registrar(VinculoId $id, string $laboratorioId, VeterinarioId $veterinarioId, QuemPaga $quemPaga, Telefone $telefoneCritico, bool $vePedidosDosColegas, RecebeLaudo $recebeLaudo, array $clinicaIds = []): self
    {
        return new self($id, $laboratorioId, $veterinarioId, $quemPaga, $telefoneCritico, $vePedidosDosColegas, $recebeLaudo, self::semRepeticao($clinicaIds));
    }

    /** @param list<string> $clinicaIds */
    public static function restaurar(VinculoId $id, string $laboratorioId, VeterinarioId $veterinarioId, QuemPaga $quemPaga, Telefone $telefoneCritico, bool $vePedidosDosColegas, RecebeLaudo $recebeLaudo, array $clinicaIds = []): self
    {
        return new self($id, $laboratorioId, $veterinarioId, $quemPaga, $telefoneCritico, $vePedidosDosColegas, $recebeLaudo, $clinicaIds);
    }

    /**
     * As preferências são do laboratório; a pessoa (nome, CRMV, e-mail) não muda aqui.
     *
     * @param  list<string>  $clinicaIds
     */
    public function redefinirPreferencias(QuemPaga $quemPaga, Telefone $telefoneCritico, bool $vePedidosDosColegas, RecebeLaudo $recebeLaudo, array $clinicaIds): self
    {
        return new self($this->id, $this->laboratorioId, $this->veterinarioId, $quemPaga, $telefoneCritico, $vePedidosDosColegas, $recebeLaudo, self::semRepeticao($clinicaIds));
    }

    public function atendeEm(string $clinicaId): bool
    {
        return in_array($clinicaId, $this->clinicaIds, true);
    }

    /**
     * @param  list<string>  $ids
     * @return list<string>
     */
    private static function semRepeticao(array $ids): array
    {
        return array_values(array_unique($ids));
    }
}
