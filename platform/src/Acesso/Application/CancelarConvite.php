<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application;

use Vidra\Acesso\Domain\ConviteInvalido;
use Vidra\Acesso\Domain\ConviteRepository;
use Vidra\Acesso\Domain\Relogio;
use Vidra\Acesso\Domain\TransacaoDaEquipe;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class CancelarConvite
{
    public function __construct(private ConviteRepository $convites, private TransacaoDaEquipe $transacao, private Relogio $relogio, private LaboratorioAtual $laboratorio) {}

    public function __invoke(string $id): void
    {
        $lab = $this->laboratorio->id();
        $this->transacao->executar($lab, function () use ($lab, $id): void {
            $convite = $this->convites->findById($lab, $id) ?? throw ConviteInvalido::para();
            $convite->cancelar($this->relogio->agora());
            $this->convites->save($convite);
        });
    }
}
