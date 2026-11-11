<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application;

use Vidra\Acesso\Domain\Equipe;
use Vidra\Acesso\Domain\ProtecaoDaEquipe;
use Vidra\Acesso\Domain\TransacaoDaEquipe;
use Vidra\Auditoria\Application\RegistrarNaTrilha;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class RemoverDaEquipe
{
    public function __construct(private Equipe $equipe, private TransacaoDaEquipe $transacao, private LaboratorioAtual $laboratorio, private RegistrarNaTrilha $trilha) {}

    public function __invoke(int $autorId, int $usuarioId): void
    {
        $lab = $this->laboratorio->id();
        $nome = $this->transacao->executar($lab, function () use ($autorId, $usuarioId, $lab): string {
            $alvo = ProtecaoDaEquipe::conferir($this->equipe->listar($lab), $autorId, $usuarioId, null);
            $this->equipe->remover($lab, $usuarioId);

            return $alvo->nome;
        });
        ($this->trilha)('pessoa_removida', 'equipe', (string) $usuarioId, 'Removeu '.$nome.' da equipe');
    }
}
