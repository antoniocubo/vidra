<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Pedidos;

use Vidra\Pedidos\Domain\Portas\SolicitanteDoPedido;
use Vidra\Pedidos\Domain\Portas\Solicitantes;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Veterinaria\Domain\Veterinario\Veterinario;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioRepository;
use Vidra\Veterinaria\Domain\Vinculo\QuemPaga;
use Vidra\Veterinaria\Domain\Vinculo\Vinculo;
use Vidra\Veterinaria\Domain\Vinculo\VinculoId;
use Vidra\Veterinaria\Domain\Vinculo\VinculoRepository;

/** Na linha Pet, o solicitante é o veterinário, identificado pelo vínculo dele com o laboratório. */
final readonly class SolicitantesVeterinaria implements Solicitantes
{
    public function __construct(private VinculoRepository $vinculos, private VeterinarioRepository $veterinarios) {}

    public function resumo(string $solicitanteId): ?SolicitanteDoPedido
    {
        try {
            $vinculo = $this->vinculos->porId(new VinculoId($solicitanteId));
        } catch (InvalidValue) {
            return null;
        }

        return $vinculo === null ? null : ($this->converter([$vinculo])[0] ?? null);
    }

    public function resumos(array $solicitanteIds): array
    {
        $resumos = [];
        foreach ($this->converter($this->vinculos->todos()) as $solicitante) {
            if (in_array($solicitante->id, $solicitanteIds, true)) {
                $resumos[$solicitante->id] = $solicitante;
            }
        }

        return $resumos;
    }

    public function todos(): array
    {
        $todos = $this->converter($this->vinculos->todos());
        usort($todos, fn (SolicitanteDoPedido $a, SolicitanteDoPedido $b): int => strcmp(mb_strtolower($a->nome), mb_strtolower($b->nome)));

        return $todos;
    }

    /**
     * @param  list<Vinculo>  $vinculos
     * @return list<SolicitanteDoPedido>
     */
    private function converter(array $vinculos): array
    {
        $veterinarios = $this->veterinarios->porIds(array_map(fn (Vinculo $v): string => $v->veterinarioId->value, $vinculos));

        $solicitantes = [];
        foreach ($vinculos as $vinculo) {
            $veterinario = $veterinarios[$vinculo->veterinarioId->value] ?? null;
            if ($veterinario instanceof Veterinario) {
                $solicitantes[] = new SolicitanteDoPedido(
                    $vinculo->id->value,
                    $veterinario->nomeDeExibicao(),
                    (string) $veterinario->crmv,
                    $veterinario->email()->value,
                    $vinculo->telefoneCritico->formatted(),
                    match ($vinculo->quemPaga) {
                        QuemPaga::Clinica => 'clinica',
                        QuemPaga::Veterinario => 'solicitante',
                        QuemPaga::Tutor => 'responsavel',
                    },
                );
            }
        }

        return $solicitantes;
    }
}
