<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\Clinica;

use Vidra\Shared\Domain\ValueObjects\Cnpj;
use Vidra\Shared\Domain\ValueObjects\Email;
use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Veterinaria\Domain\Clinica\DadosDaClinica;
use Vidra\Veterinaria\Domain\Clinica\Faturamento;
use Vidra\Veterinaria\Domain\Clinica\FormaDePagamento;

final readonly class MontarDadosDaClinica
{
    public function __invoke(DadosDaClinicaInput $input): DadosDaClinica
    {
        return new DadosDaClinica(
            nomeFantasia: $input->nomeFantasia,
            razaoSocial: $input->razaoSocial,
            cnpj: self::preenchido($input->cnpj) ? new Cnpj((string) $input->cnpj) : null,
            cidade: $input->cidade,
            bairro: $input->bairro,
            emailDeLaudos: self::preenchido($input->emailDeLaudos) ? new Email((string) $input->emailDeLaudos) : null,
            telefoneCritico: self::preenchido($input->telefoneCritico) ? new Telefone((string) $input->telefoneCritico) : null,
            responsavel: $input->responsavel,
            observacaoDeColeta: $input->observacaoDeColeta,
            faturamento: new Faturamento(
                new Email($input->emailDeFaturamento),
                $input->desconto,
                $input->diaDeFechamento,
                $input->diaDeVencimento,
                FormaDePagamento::from($input->formaDePagamento),
            ),
        );
    }

    private static function preenchido(?string $valor): bool
    {
        return $valor !== null && trim($valor) !== '';
    }
}
