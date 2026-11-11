<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application;

use Vidra\Acesso\Domain\Convite;
use Vidra\Acesso\Domain\ConviteInvalido;
use Vidra\Acesso\Domain\ConviteRepository;
use Vidra\Acesso\Domain\EntregaDeConvites;
use Vidra\Acesso\Domain\Equipe;
use Vidra\Acesso\Domain\JaFazParteDaEquipe;
use Vidra\Acesso\Domain\Relogio;
use Vidra\Acesso\Domain\TokensDeConvite;
use Vidra\Acesso\Domain\TransacaoDaEquipe;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class ReenviarConvite
{
    public function __construct(private ConviteRepository $convites, private Equipe $equipe, private EntregaDeConvites $entrega, private TokensDeConvite $tokens, private TransacaoDaEquipe $transacao, private Relogio $relogio, private LaboratorioAtual $laboratorio) {}

    public function __invoke(string $id): void
    {
        $lab = $this->laboratorio->id();
        $this->transacao->executar($lab, function () use ($id, $lab): void {
            $anterior = $this->convites->findById($lab, $id) ?? throw ConviteInvalido::para();
            $anterior->cancelar($this->relogio->agora());
            if ($this->equipe->contemEmail($lab, $anterior->email)) {
                throw JaFazParteDaEquipe::para();
            }
            $this->convites->save($anterior);
            $token = $this->tokens->gerar();
            $novo = Convite::emitir($this->convites->nextIdentity(), $lab, $anterior->nome, $anterior->email, $anterior->papel, $token, $anterior->convidadoPor, $this->relogio->agora());
            $this->convites->save($novo);
            $this->entrega->enviar($novo, $token);
        });
    }
}
