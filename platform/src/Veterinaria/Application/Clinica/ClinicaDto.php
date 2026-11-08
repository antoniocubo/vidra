<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\Clinica;

use Vidra\Veterinaria\Domain\Clinica\Clinica;

final readonly class ClinicaDto
{
    /** @param list<VeterinarioDaClinicaDto> $veterinarios */
    public function __construct(
        public string $id,
        public string $nomeFantasia,
        public ?string $razaoSocial,
        public ?string $cnpj,
        public string $cidade,
        public ?string $bairro,
        public ?string $emailDeLaudos,
        public string $emailDeFaturamento,
        public ?string $telefoneCritico,
        public ?string $responsavel,
        public ?string $observacaoDeColeta,
        public int $desconto,
        public int $diaDeFechamento,
        public int $diaDeVencimento,
        public string $formaDePagamento,
        public bool $ativa,
        public array $veterinarios = [],
    ) {}

    /** @param list<VeterinarioDaClinicaDto> $veterinarios */
    public static function de(Clinica $clinica, array $veterinarios = []): self
    {
        $dados = $clinica->dados();

        return new self(
            id: $clinica->id->value,
            nomeFantasia: $dados->nomeFantasia,
            razaoSocial: $dados->razaoSocial,
            cnpj: $dados->cnpj?->formatted(),
            cidade: $dados->cidade,
            bairro: $dados->bairro,
            emailDeLaudos: $dados->emailDeLaudos?->value,
            emailDeFaturamento: $dados->faturamento->email->value,
            telefoneCritico: $dados->telefoneCritico?->formatted(),
            responsavel: $dados->responsavel,
            observacaoDeColeta: $dados->observacaoDeColeta,
            desconto: $dados->faturamento->desconto,
            diaDeFechamento: $dados->faturamento->diaDeFechamento,
            diaDeVencimento: $dados->faturamento->diaDeVencimento,
            formaDePagamento: $dados->faturamento->forma->value,
            ativa: $clinica->ativa(),
            veterinarios: $veterinarios,
        );
    }
}
