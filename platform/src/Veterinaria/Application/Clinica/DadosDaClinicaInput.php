<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\Clinica;

/** Campos editáveis da clínica, só com tipos primitivos. Serve ao cadastro e à alteração. */
final readonly class DadosDaClinicaInput
{
    public function __construct(
        public string $nomeFantasia,
        public string $cidade,
        public string $emailDeFaturamento,
        public int $desconto,
        public int $diaDeFechamento,
        public int $diaDeVencimento,
        public string $formaDePagamento,
        public ?string $razaoSocial = null,
        public ?string $cnpj = null,
        public ?string $bairro = null,
        public ?string $emailDeLaudos = null,
        public ?string $telefoneCritico = null,
        public ?string $responsavel = null,
        public ?string $observacaoDeColeta = null,
    ) {}
}
