<?php

declare(strict_types=1);

namespace Tests\Support;

use Vidra\Acesso\Domain\Equipe;
use Vidra\Acesso\Domain\MembroDaEquipe;
use Vidra\Acesso\Domain\Papel;

final class InMemoryEquipe implements Equipe
{
    /** @var array<string,array<int,MembroDaEquipe>> */
    public array $dados = [];

    public function listar(string $laboratorioId): array
    {
        return array_values($this->dados[$laboratorioId] ?? []);
    }

    public function contemEmail(string $laboratorioId, string $email): bool
    {
        foreach ($this->listar($laboratorioId) as $membro) {
            if (strtolower($membro->email) === strtolower($email)) {
                return true;
            }
        }

        return false;
    }

    public function adicionar(string $laboratorioId, int $usuarioId, Papel $papel): void
    {
        $this->dados[$laboratorioId][$usuarioId] = new MembroDaEquipe($usuarioId, 'Ana Souza', 'ana@example.com', $papel, $papel === Papel::Rt);
    }

    public function trocarPapel(string $laboratorioId, int $usuarioId, Papel $papel, bool $eRt): void
    {
        $m = $this->dados[$laboratorioId][$usuarioId];
        $this->dados[$laboratorioId][$usuarioId] = new MembroDaEquipe($m->id, $m->nome, $m->email, $papel, $eRt);
    }

    public function remover(string $laboratorioId, int $usuarioId): void
    {
        unset($this->dados[$laboratorioId][$usuarioId]);
    }

    public function nomeDoLaboratorio(string $laboratorioId): string
    {
        return 'Laboratório Alfa';
    }

    public function nomeDoUsuario(int $usuarioId): string
    {
        return 'Marina';
    }
}
