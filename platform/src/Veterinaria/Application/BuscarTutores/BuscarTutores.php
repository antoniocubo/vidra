<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\BuscarTutores;

use Vidra\Veterinaria\Application\DetalharTutor\DetalharTutor;
use Vidra\Veterinaria\Application\DetalharTutor\DetalharTutorInput;
use Vidra\Veterinaria\Application\DetalharTutor\TutorDto;
use Vidra\Veterinaria\Domain\Tutor\Tutor;
use Vidra\Veterinaria\Domain\Tutor\TutorRepository;

final readonly class BuscarTutores
{
    public function __construct(private TutorRepository $tutores, private DetalharTutor $detalhar) {}

    /** @return list<TutorDto> */
    public function __invoke(BuscarTutoresInput $input): array
    {
        return array_map(fn (Tutor $t): TutorDto => ($this->detalhar)(new DetalharTutorInput($t->id->value)), $this->tutores->buscar($input->termo, 50));
    }
}
