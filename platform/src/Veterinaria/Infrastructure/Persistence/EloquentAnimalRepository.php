<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Persistence;

use Illuminate\Support\Str;
use Vidra\Veterinaria\Domain\Animal\Animal;
use Vidra\Veterinaria\Domain\Animal\AnimalId;
use Vidra\Veterinaria\Domain\Animal\AnimalRepository;
use Vidra\Veterinaria\Domain\Animal\Castrado;
use Vidra\Veterinaria\Domain\Animal\Especie;
use Vidra\Veterinaria\Domain\Animal\Sexo;
use Vidra\Veterinaria\Domain\Tutor\TutorId;
use Vidra\Veterinaria\Domain\Vinculo\VinculoId;

final class EloquentAnimalRepository implements AnimalRepository
{
    public function nextIdentity(): AnimalId
    {
        return new AnimalId((string) Str::uuid7());
    }

    public function save(Animal $animal): void
    {
        AnimalModel::query()->updateOrCreate(['id' => $animal->id->value], [
            'laboratorio_id' => $animal->laboratorioId, 'tutor_id' => $animal->tutorId->value,
            'nome' => $animal->nome, 'especie' => $animal->especie->value, 'raca' => $animal->raca,
            'sexo' => $animal->sexo->value, 'nascimento' => $animal->nascimento->format('Y-m-d'),
            'nascimento_estimado' => $animal->nascimentoEstimado, 'peso_gramas' => $animal->pesoGramas,
            'castrado' => $animal->castrado->value, 'microchip' => $animal->microchip,
            'vinculo_veterinario_id' => $animal->vinculoVeterinarioId?->value,
        ]);
    }

    public function porId(AnimalId $id): ?Animal
    {
        $m = AnimalModel::query()->find($id->value);

        return $m === null ? null : $this->entity($m);
    }

    public function doTutor(TutorId $id): array
    {
        return array_values(AnimalModel::query()->where('tutor_id', $id->value)->orderBy('created_at')->orderBy('id')->get()->map(fn (AnimalModel $m): Animal => $this->entity($m))->all());
    }

    private function entity(AnimalModel $m): Animal
    {
        return Animal::restaurar(new AnimalId($m->id), $m->laboratorio_id, new TutorId($m->tutor_id), $m->nome, Especie::from($m->especie), $m->raca, Sexo::from($m->sexo), $m->nascimento->toDateTimeImmutable(), $m->nascimento_estimado, $m->peso_gramas, Castrado::from($m->castrado), $m->microchip, $m->vinculo_veterinario_id === null ? null : new VinculoId($m->vinculo_veterinario_id));
    }
}
