<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\AlterarTutor;

final readonly class AlterarTutorInput
{
    public function __construct(public string $tutorId, public string $nome, public string $celular, public ?string $email, public ?string $cpf, public ?string $cep, public bool $autorizaEmail, public int $atendenteId) {}
}
