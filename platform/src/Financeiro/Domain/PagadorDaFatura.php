<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Domain;

/** Cópia de quem paga no momento do fechamento: mudar o cadastro depois não muda a fatura. */
final readonly class PagadorDaFatura
{
    public function __construct(
        public TipoDePagadorDaFatura $tipo,
        public string $id,
        public string $nome,
        public ?string $email,
    ) {}
}
