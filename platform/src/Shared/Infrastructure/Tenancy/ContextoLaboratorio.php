<?php

declare(strict_types=1);

namespace Vidra\Shared\Infrastructure\Tenancy;

use Closure;
use Illuminate\Database\Connection;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Shared\Domain\Tenancy\LaboratorioNaoDefinido;

/**
 * Guarda o laboratório da requisição e o repassa ao Postgres em `app.laboratorio_id`,
 * que as policies de RLS leem. Registrado como `scoped`: um por requisição ou job.
 */
final class ContextoLaboratorio implements LaboratorioAtual
{
    private ?string $id = null;

    public function __construct(private readonly Connection $conexao) {}

    public function id(): string
    {
        return $this->id ?? throw LaboratorioNaoDefinido::naConsulta();
    }

    public function definido(): bool
    {
        return $this->id !== null;
    }

    public function definir(string $laboratorioId): void
    {
        $this->id = $laboratorioId;
        $this->repassarAoBanco($laboratorioId);
    }

    public function limpar(): void
    {
        $this->id = null;
        $this->repassarAoBanco('');
    }

    /**
     * Para jobs e comandos: roda o trecho dentro de um laboratório e volta ao anterior.
     *
     * @template T
     *
     * @param  Closure(): T  $trecho
     * @return T
     */
    public function executarComo(string $laboratorioId, Closure $trecho): mixed
    {
        $anterior = $this->id;
        $this->definir($laboratorioId);

        try {
            return $trecho();
        } finally {
            $anterior === null ? $this->limpar() : $this->definir($anterior);
        }
    }

    private function repassarAoBanco(string $valor): void
    {
        if ($this->conexao->getDriverName() !== 'pgsql') {
            return;
        }

        // is_local = false: vale para a sessão inteira, não só para a transação atual.
        $this->conexao->select("select set_config('app.laboratorio_id', ?, false)", [$valor]);
    }
}
