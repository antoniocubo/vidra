<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain\Assinatura;

/** Os dados do laboratório de que o gateway precisa, mais os ids que ele já nos deu. */
final readonly class ClienteDeCobranca
{
    public function __construct(
        public string $laboratorioId,
        public string $nome,
        public string $cnpj,
        public ?string $email,
        public ?string $clienteId,
        public ?string $assinaturaId,
    ) {}
}
