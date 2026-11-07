<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Veterinario;

use Vidra\Shared\Domain\ValueObjects\Email;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Shared\Domain\ValueObjects\Telefone;

/**
 * Veterinário solicitante. É uma pessoa só no Vidra, identificada pelo CRMV,
 * e pode estar vinculada a vários laboratórios (o vínculo é outro agregado).
 */
final class Veterinario
{
    private function __construct(
        public readonly VeterinarioId $id,
        private Tratamento $tratamento,
        private string $nome,
        public readonly Crmv $crmv,
        private Email $email,
        private Telefone $celular,
        private Telefone $telefoneCritico,
        private ?string $especialidade,
    ) {}

    public static function registrar(
        VeterinarioId $id,
        Tratamento $tratamento,
        string $nome,
        Crmv $crmv,
        Email $email,
        Telefone $celular,
        Telefone $telefoneCritico,
        ?string $especialidade = null,
    ): self {
        return new self(
            $id,
            $tratamento,
            self::normalizarNome($nome),
            $crmv,
            $email,
            $celular,
            $telefoneCritico,
            self::normalizarOpcional($especialidade),
        );
    }

    /** Reconstrói a partir da persistência, sem rodar regras de criação. */
    public static function restaurar(
        VeterinarioId $id,
        Tratamento $tratamento,
        string $nome,
        Crmv $crmv,
        Email $email,
        Telefone $celular,
        Telefone $telefoneCritico,
        ?string $especialidade,
    ): self {
        return new self($id, $tratamento, $nome, $crmv, $email, $celular, $telefoneCritico, $especialidade);
    }

    /** Nome como sai no laudo: "Dra. Beatriz Nogueira". */
    public function nomeDeExibicao(): string
    {
        return $this->tratamento->prefixo().$this->nome;
    }

    public function tratamento(): Tratamento
    {
        return $this->tratamento;
    }

    public function nome(): string
    {
        return $this->nome;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function celular(): Telefone
    {
        return $this->celular;
    }

    public function telefoneCritico(): Telefone
    {
        return $this->telefoneCritico;
    }

    public function especialidade(): ?string
    {
        return $this->especialidade;
    }

    private static function normalizarNome(string $nome): string
    {
        $nome = preg_replace('/\s+/', ' ', trim($nome)) ?? '';

        if (mb_strlen($nome) < 3) {
            throw InvalidValue::for('nome', 'informe o nome completo');
        }

        return $nome;
    }

    private static function normalizarOpcional(?string $valor): ?string
    {
        $valor = $valor === null ? null : trim($valor);

        return $valor === '' ? null : $valor;
    }
}
