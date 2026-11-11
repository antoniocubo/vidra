<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application;

use Vidra\Acesso\Domain\Equipe;
use Vidra\Acesso\Domain\Papel;
use Vidra\Acesso\Domain\ProtecaoDaEquipe;
use Vidra\Acesso\Domain\TransacaoDaEquipe;
use Vidra\Auditoria\Application\RegistrarNaTrilha;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class TrocarPapel
{
    public function __construct(private Equipe $equipe, private TransacaoDaEquipe $transacao, private LaboratorioAtual $laboratorio, private RegistrarNaTrilha $trilha) {}

    public function __invoke(int $autorId, int $usuarioId, string $papel): void
    {
        $lab = $this->laboratorio->id();
        $nome = $this->transacao->executar($lab, function () use ($autorId, $usuarioId, $lab, $papel): string {
            $novo = Papel::from($papel);
            $alvo = ProtecaoDaEquipe::conferir($this->equipe->listar($lab), $autorId, $usuarioId, $novo);
            $this->equipe->trocarPapel($lab, $usuarioId, $novo, $novo === Papel::Rt || ($novo === Papel::Admin && $alvo->eRt));

            return $alvo->nome;
        });
        ($this->trilha)('papel_trocado', 'equipe', (string) $usuarioId, 'Trocou o papel de '.$nome.' para '.Papel::from($papel)->rotulo());
    }
}
