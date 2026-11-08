<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\CadastrarTutor;

/** @phpstan-import-type AnimalDados from \Vidra\Veterinaria\Application\AdicionarAnimal\AdicionarAnimalInput */
final readonly class CadastrarTutorInput
{
    /** @param list<AnimalDados> $animais */
    public function __construct(public string $nome, public string $celular, public ?string $email, public ?string $cpf, public ?string $cep, public bool $autorizaEmail, public int $atendenteId, public array $animais) {}
}
