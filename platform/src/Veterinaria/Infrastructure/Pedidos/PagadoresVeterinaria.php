<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Pedidos;

use Vidra\Pedidos\Domain\Portas\ClinicaPagadora;
use Vidra\Pedidos\Domain\Portas\Pagadores;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Veterinaria\Domain\Clinica\Clinica;
use Vidra\Veterinaria\Domain\Clinica\ClinicaId;
use Vidra\Veterinaria\Domain\Clinica\ClinicaRepository;
use Vidra\Veterinaria\Domain\Vinculo\VinculoId;
use Vidra\Veterinaria\Domain\Vinculo\VinculoRepository;

/** Na linha Pet, quem recebe a fatura mensal é a clínica parceira. */
final readonly class PagadoresVeterinaria implements Pagadores
{
    public function __construct(private ClinicaRepository $clinicas, private VinculoRepository $vinculos) {}

    public function clinicasDoSolicitante(string $solicitanteId): array
    {
        try {
            $vinculo = $this->vinculos->porId(new VinculoId($solicitanteId));
        } catch (InvalidValue) {
            return [];
        }
        if ($vinculo === null) {
            return [];
        }

        $ativas = array_filter($this->clinicas->porIds($vinculo->clinicaIds), fn (Clinica $c): bool => $c->ativa());

        return $this->ordenar(array_values(array_map($this->converter(...), $ativas)));
    }

    public function clinica(string $id): ?ClinicaPagadora
    {
        try {
            $clinica = $this->clinicas->porId(new ClinicaId($id));
        } catch (InvalidValue) {
            return null;
        }

        return $clinica === null ? null : $this->converter($clinica);
    }

    public function clinicas(array $ids): array
    {
        $clinicas = [];
        foreach ($this->clinicas->porIds($ids) as $id => $clinica) {
            $clinicas[$id] = $this->converter($clinica);
        }

        return $clinicas;
    }

    public function todasAtivas(): array
    {
        $ativas = array_filter($this->clinicas->todas(), fn (Clinica $c): bool => $c->ativa());

        return $this->ordenar(array_values(array_map($this->converter(...), $ativas)));
    }

    private function converter(Clinica $clinica): ClinicaPagadora
    {
        return new ClinicaPagadora($clinica->id->value, $clinica->dados()->nomeFantasia, $clinica->dados()->faturamento->desconto, $clinica->ativa());
    }

    /**
     * @param  list<ClinicaPagadora>  $clinicas
     * @return list<ClinicaPagadora>
     */
    private function ordenar(array $clinicas): array
    {
        usort($clinicas, fn (ClinicaPagadora $a, ClinicaPagadora $b): int => strcmp(mb_strtolower($a->nome), mb_strtolower($b->nome)));

        return $clinicas;
    }
}
