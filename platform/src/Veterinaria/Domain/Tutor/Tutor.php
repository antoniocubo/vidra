<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Tutor;

use DateTimeImmutable;
use Vidra\Shared\Domain\ValueObjects\Email;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Veterinaria\Domain\Tutor\Exceptions\TutorSemEmail;

final class Tutor
{
    private function __construct(
        public readonly TutorId $id, public readonly string $laboratorioId,
        private string $nome, private Telefone $celular, private ?Email $email,
        private ?Cpf $cpf, private ?string $cep, private ?AutorizacaoDeEmail $autorizacao,
    ) {}

    public static function registrar(TutorId $id, string $laboratorioId, string $nome, Telefone $celular, ?Email $email = null, ?Cpf $cpf = null, ?string $cep = null): self
    {
        $tutor = new self($id, $laboratorioId, '', $celular, null, null, null, null);
        $tutor->alterarContato($nome, $celular, $email, $cpf, $cep);

        return $tutor;
    }

    public static function restaurar(TutorId $id, string $laboratorioId, string $nome, Telefone $celular, ?Email $email, ?Cpf $cpf, ?string $cep, ?AutorizacaoDeEmail $autorizacao): self
    {
        return new self($id, $laboratorioId, $nome, $celular, $email, $cpf, $cep, $autorizacao);
    }

    public function alterarContato(string $nome, Telefone $celular, ?Email $email, ?Cpf $cpf, ?string $cep): void
    {
        $nome = preg_replace('/\s+/', ' ', trim($nome)) ?? '';
        if (mb_strlen($nome) < 3) {
            throw InvalidValue::for('nome', 'informe o nome completo');
        }
        if ($cep !== null && ! preg_match('/^\d{8}$/', $cep)) {
            throw InvalidValue::for('CEP', 'use 8 dígitos');
        }
        if ($this->email?->value !== $email?->value) {
            $this->retirarAutorizacao();
        }
        $this->nome = $nome;
        $this->celular = $celular;
        $this->email = $email;
        $this->cpf = $cpf;
        $this->cep = $cep;
    }

    public function autorizarLaudoPorEmail(int $atendente, DateTimeImmutable $quando): void
    {
        if ($this->email === null) {
            throw TutorSemEmail::para();
        }
        $this->autorizacao ??= new AutorizacaoDeEmail($quando, $atendente);
    }

    public function retirarAutorizacao(): void
    {
        $this->autorizacao = null;
    }

    public function nome(): string
    {
        return $this->nome;
    }

    public function celular(): Telefone
    {
        return $this->celular;
    }

    public function email(): ?Email
    {
        return $this->email;
    }

    public function cpf(): ?Cpf
    {
        return $this->cpf;
    }

    public function cep(): ?string
    {
        return $this->cep;
    }

    public function autorizacao(): ?AutorizacaoDeEmail
    {
        return $this->autorizacao;
    }
}
