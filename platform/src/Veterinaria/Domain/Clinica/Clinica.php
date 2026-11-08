<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Clinica;

/**
 * Clínica parceira: cadastro que recebe a fatura mensal por e-mail. Não tem login.
 */
final class Clinica
{
    private function __construct(
        public readonly ClinicaId $id,
        public readonly string $laboratorioId,
        private DadosDaClinica $dados,
        private bool $ativa,
    ) {}

    public static function registrar(ClinicaId $id, string $laboratorioId, DadosDaClinica $dados): self
    {
        return new self($id, $laboratorioId, $dados, true);
    }

    /** Reconstrói a partir da persistência, sem rodar regras de criação. */
    public static function restaurar(ClinicaId $id, string $laboratorioId, DadosDaClinica $dados, bool $ativa): self
    {
        return new self($id, $laboratorioId, $dados, $ativa);
    }

    public function dados(): DadosDaClinica
    {
        return $this->dados;
    }

    public function ativa(): bool
    {
        return $this->ativa;
    }

    public function corrigirCadastro(DadosDaClinica $dados): void
    {
        $this->dados = $dados;
    }

    public function desativar(): void
    {
        $this->ativa = false;
    }

    public function reativar(): void
    {
        $this->ativa = true;
    }
}
