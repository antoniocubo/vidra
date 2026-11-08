<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Domain\Referencia;

/**
 * Um grupo de pacientes que compartilha a mesma faixa de referência.
 * O núcleo não sabe o que é espécie ou gestação: só conhece a chave
 * ("cao.adulto", "gato.idoso"...), que a linha de produto define.
 */
final readonly class PerfilDeReferencia
{
    public function __construct(
        public string $chave,
        public string $nome,
        /** Agrupa na tela, por exemplo "Cão" ou "Gato". */
        public string $grupo,
    ) {}
}
