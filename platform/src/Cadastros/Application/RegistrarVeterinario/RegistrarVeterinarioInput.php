<?php

declare(strict_types=1);

namespace Vidra\Cadastros\Application\RegistrarVeterinario;

/**
 * Dados de entrada do caso de uso, só com tipos primitivos.
 * Quem chama (controller, comando, fila) não precisa conhecer o domínio.
 */
final readonly class RegistrarVeterinarioInput
{
    public function __construct(
        public string $tratamento,
        public string $nome,
        public string $crmvNumero,
        public string $crmvUf,
        public string $email,
        public string $celular,
        public string $telefoneCritico,
        public ?string $especialidade = null,
    ) {}
}
