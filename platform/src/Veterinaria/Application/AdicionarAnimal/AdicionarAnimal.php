<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\AdicionarAnimal;

use DateTimeImmutable;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Veterinaria\Domain\Animal\Animal;
use Vidra\Veterinaria\Domain\Animal\AnimalId;
use Vidra\Veterinaria\Domain\Animal\AnimalRepository;
use Vidra\Veterinaria\Domain\Animal\Castrado;
use Vidra\Veterinaria\Domain\Animal\Especie;
use Vidra\Veterinaria\Domain\Animal\Sexo;
use Vidra\Veterinaria\Domain\Relogio;
use Vidra\Veterinaria\Domain\Tutor\Exceptions\TutorNaoEncontrado;
use Vidra\Veterinaria\Domain\Tutor\TutorId;
use Vidra\Veterinaria\Domain\Tutor\TutorRepository;
use Vidra\Veterinaria\Domain\Vinculo\VinculoId;
use Vidra\Veterinaria\Domain\Vinculo\VinculosParaEscolha;

final readonly class AdicionarAnimal
{
    public function __construct(private AnimalRepository $animais, private TutorRepository $tutores, private LaboratorioAtual $laboratorio, private VinculosParaEscolha $vinculos, private Relogio $relogio) {}

    public function __invoke(AdicionarAnimalInput $input): AnimalId
    {
        $tutor = $this->tutores->porId(new TutorId($input->tutorId)) ?? throw TutorNaoEncontrado::para();
        $a = $input->animal;
        if ($a['vinculoVeterinarioId'] !== null && ! isset($this->vinculos->nomes()[$a['vinculoVeterinarioId']])) {
            throw InvalidValue::for('vinculo_veterinario_id', 'escolha um veterinário deste laboratório');
        }
        $nascimento = new DateTimeImmutable($a['nascimento']);
        if ($nascimento > $this->relogio->agora()) {
            throw InvalidValue::for('nascimento', 'a data não pode ser futura');
        }
        $animal = Animal::registrar($this->animais->nextIdentity(), $this->laboratorio->id(), $tutor->id, $a['nome'], Especie::from($a['especie']), $a['raca'], Sexo::from($a['sexo']), $nascimento, $a['nascimentoEstimado'], $a['pesoGramas'], Castrado::from($a['castrado']), $a['microchip'], $a['vinculoVeterinarioId'] === null ? null : new VinculoId($a['vinculoVeterinarioId']));
        $this->animais->save($animal);

        return $animal->id;
    }
}
