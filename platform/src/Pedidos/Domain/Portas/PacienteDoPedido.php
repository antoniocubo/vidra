<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Portas;

/** O paciente como o pedido o enxerga, sem saber se é animal ou pessoa. */
final readonly class PacienteDoPedido
{
    public function __construct(
        public string $id,
        public string $nome,
        /** Ex.: "Cão · Golden Retriever · macho · 7 anos · 32 kg". */
        public string $descricao,
        /** Para listas e etiquetas: "cão", "gata". */
        public string $especieCurta,
        /** Chave do perfil de referência do Catálogo, quando há. */
        public ?string $perfilDeReferencia,
        public string $responsavelNome,
        public ?string $responsavelEmail,
        public bool $autorizaEmail,
        public string $responsavelTelefone,
    ) {}
}
