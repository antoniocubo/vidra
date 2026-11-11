<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

interface Equipe
{
    /** @return list<MembroDaEquipe> */
    public function listar(string $laboratorioId): array;

    public function contemEmail(string $laboratorioId, string $email): bool;

    public function adicionar(string $laboratorioId, int $usuarioId, Papel $papel): void;

    public function trocarPapel(string $laboratorioId, int $usuarioId, Papel $papel, bool $eRt): void;

    public function remover(string $laboratorioId, int $usuarioId): void;

    public function nomeDoLaboratorio(string $laboratorioId): string;

    public function nomeDoUsuario(int $usuarioId): string;
}
