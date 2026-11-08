<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\DetalharTutor;

final readonly class TutorDto
{
    /** @param list<AnimalDto> $animais */
    public function __construct(public string $id, public string $nome, public string $celular, public ?string $email, public ?string $cpf, public ?string $cep, public ?string $autorizadoEm, public ?string $autorizadoPor, public array $animais) {}
}
