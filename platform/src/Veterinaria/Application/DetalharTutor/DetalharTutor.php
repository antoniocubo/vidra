<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\DetalharTutor;

use DateTimeZone;
use Vidra\Veterinaria\Domain\Animal\Animal;
use Vidra\Veterinaria\Domain\Animal\AnimalRepository;
use Vidra\Veterinaria\Domain\Relogio;
use Vidra\Veterinaria\Domain\Tutor\AtendentesDoTutor;
use Vidra\Veterinaria\Domain\Tutor\Exceptions\TutorNaoEncontrado;
use Vidra\Veterinaria\Domain\Tutor\TutorId;
use Vidra\Veterinaria\Domain\Tutor\TutorRepository;
use Vidra\Veterinaria\Domain\Vinculo\VinculosParaEscolha;

final readonly class DetalharTutor
{
    public function __construct(private TutorRepository $tutores, private AnimalRepository $animais, private VinculosParaEscolha $vinculos, private AtendentesDoTutor $atendentes, private Relogio $relogio) {}

    public function __invoke(DetalharTutorInput $input): TutorDto
    {
        $t = $this->tutores->porId(new TutorId($input->tutorId)) ?? throw TutorNaoEncontrado::para();
        $nomes = $this->vinculos->nomes();
        $hoje = $this->relogio->agora();
        $autorizacao = $t->autorizacao();

        return new TutorDto($t->id->value, $t->nome(), $t->celular()->formatted(), $t->email()?->value, $t->cpf()?->digits, $t->cep(), $autorizacao?->registradaEm->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y \à\s H:i'), $autorizacao === null ? null : $this->atendentes->nome($autorizacao->registradaPor), array_map(fn (Animal $a): AnimalDto => new AnimalDto($a->id->value, $a->nome, $a->descricao($hoje), $nomes[$a->vinculoVeterinarioId->value ?? ''] ?? null, $a->perfilDeReferencia($hoje), $a->nascimento->format('d/m/Y'), $a->microchip), $this->animais->doTutor($t->id)));
    }
}
