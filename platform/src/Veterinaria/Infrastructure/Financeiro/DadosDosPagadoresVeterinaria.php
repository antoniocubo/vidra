<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Financeiro;

use Vidra\Financeiro\Domain\DadosDoPagador;
use Vidra\Financeiro\Domain\DadosDosPagadores;
use Vidra\Financeiro\Domain\PagadorDaFatura;
use Vidra\Financeiro\Domain\TipoDePagadorDaFatura;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Veterinaria\Domain\Animal\AnimalId;
use Vidra\Veterinaria\Domain\Animal\AnimalRepository;
use Vidra\Veterinaria\Domain\Clinica\ClinicaId;
use Vidra\Veterinaria\Domain\Clinica\ClinicaRepository;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioRepository;
use Vidra\Veterinaria\Domain\Vinculo\VinculoId;
use Vidra\Veterinaria\Domain\Vinculo\VinculoRepository;

/** Na linha Pet, quem recebe fatura é a clínica ou o veterinário (pelo vínculo) e o paciente é o animal. */
final readonly class DadosDosPagadoresVeterinaria implements DadosDosPagadores
{
    public function __construct(private ClinicaRepository $clinicas, private VinculoRepository $vinculos, private VeterinarioRepository $veterinarios, private AnimalRepository $animais) {}

    public function clinica(string $id): ?DadosDoPagador
    {
        try {
            $clinica = $this->clinicas->porId(new ClinicaId($id));
        } catch (InvalidValue) {
            return null;
        }
        if ($clinica === null) {
            return null;
        }
        $faturamento = $clinica->dados()->faturamento;

        return new DadosDoPagador(
            new PagadorDaFatura(TipoDePagadorDaFatura::Clinica, $id, $clinica->dados()->nomeFantasia, $faturamento->email->value),
            $faturamento->diaDeFechamento, $faturamento->diaDeVencimento,
        );
    }

    public function solicitante(string $id): ?DadosDoPagador
    {
        try {
            $vinculo = $this->vinculos->porId(new VinculoId($id));
        } catch (InvalidValue) {
            return null;
        }
        $veterinario = $vinculo === null ? null : ($this->veterinarios->porIds([$vinculo->veterinarioId->value])[$vinculo->veterinarioId->value] ?? null);
        if ($veterinario === null) {
            return null;
        }

        return DadosDoPagador::doSolicitante(new PagadorDaFatura(TipoDePagadorDaFatura::Solicitante, $id, $veterinario->nomeDeExibicao(), $veterinario->email()->value));
    }

    public function pacientes(array $ids): array
    {
        $nomes = [];
        foreach ($ids as $id) {
            try {
                $animal = $this->animais->porId(new AnimalId($id));
            } catch (InvalidValue) {
                continue;
            }
            if ($animal !== null) {
                $nomes[$id] = $animal->nome;
            }
        }

        return $nomes;
    }
}
