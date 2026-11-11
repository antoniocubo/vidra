<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application;

use Vidra\Acesso\Domain\Equipe;
use Vidra\Acesso\Domain\ProtecaoDaEquipe;
use Vidra\Acesso\Domain\TransacaoDaEquipe;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class RemoverDaEquipe
{
    public function __construct(private Equipe $equipe, private TransacaoDaEquipe $transacao, private LaboratorioAtual $laboratorio) {}

    public function __invoke(int $autorId, int $usuarioId): void
    {
        $lab = $this->laboratorio->id();
        $this->transacao->executar($lab, function () use ($autorId, $usuarioId, $lab): void {
            ProtecaoDaEquipe::conferir($this->equipe->listar($lab), $autorId, $usuarioId, null);
            $this->equipe->remover($lab, $usuarioId);
        });
    }
}
