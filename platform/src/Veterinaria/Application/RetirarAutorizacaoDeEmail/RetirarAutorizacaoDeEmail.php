<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\RetirarAutorizacaoDeEmail;

use Vidra\Veterinaria\Domain\Tutor\Exceptions\TutorNaoEncontrado;
use Vidra\Veterinaria\Domain\Tutor\TutorId;
use Vidra\Veterinaria\Domain\Tutor\TutorRepository;

final readonly class RetirarAutorizacaoDeEmail
{
    public function __construct(private TutorRepository $tutores) {}

    public function __invoke(RetirarAutorizacaoDeEmailInput $input): TutorId
    {
        $tutor = $this->tutores->porId(new TutorId($input->tutorId)) ?? throw TutorNaoEncontrado::para();
        $tutor->retirarAutorizacao();
        $this->tutores->save($tutor);

        return $tutor->id;
    }
}
