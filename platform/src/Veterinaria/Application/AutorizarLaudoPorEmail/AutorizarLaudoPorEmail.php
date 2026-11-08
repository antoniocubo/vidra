<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\AutorizarLaudoPorEmail;

use Vidra\Veterinaria\Domain\Relogio;
use Vidra\Veterinaria\Domain\Tutor\Exceptions\TutorNaoEncontrado;
use Vidra\Veterinaria\Domain\Tutor\TutorId;
use Vidra\Veterinaria\Domain\Tutor\TutorRepository;

final readonly class AutorizarLaudoPorEmail
{
    public function __construct(private TutorRepository $tutores, private Relogio $relogio) {}

    public function __invoke(AutorizarLaudoPorEmailInput $input): TutorId
    {
        $tutor = $this->tutores->porId(new TutorId($input->tutorId)) ?? throw TutorNaoEncontrado::para();
        $tutor->autorizarLaudoPorEmail($input->atendenteId, $this->relogio->agora());
        $this->tutores->save($tutor);

        return $tutor->id;
    }
}
