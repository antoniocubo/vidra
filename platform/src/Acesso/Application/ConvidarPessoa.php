<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application;

use Vidra\Acesso\Domain\Convite;
use Vidra\Acesso\Domain\ConviteRepository;
use Vidra\Acesso\Domain\EntregaDeConvites;
use Vidra\Acesso\Domain\Equipe;
use Vidra\Acesso\Domain\JaFazParteDaEquipe;
use Vidra\Acesso\Domain\Papel;
use Vidra\Acesso\Domain\Relogio;
use Vidra\Acesso\Domain\TokensDeConvite;
use Vidra\Acesso\Domain\TransacaoDaEquipe;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class ConvidarPessoa
{
    public function __construct(private ConviteRepository $convites, private Equipe $equipe, private EntregaDeConvites $entrega, private TokensDeConvite $tokens, private TransacaoDaEquipe $transacao, private Relogio $relogio, private LaboratorioAtual $laboratorio) {}

    public function __invoke(ConvidarPessoaInput $input): string
    {
        $lab = $this->laboratorio->id();

        return $this->transacao->executar($lab, function () use ($input, $lab): string {
            $email = (new \Vidra\Shared\Domain\ValueObjects\Email($input->email))->value;
            if ($this->equipe->contemEmail($lab, $email)) {
                throw JaFazParteDaEquipe::para();
            }
            foreach ($this->convites->pendentes($lab) as $anterior) {
                if ($anterior->email === $email) {
                    $anterior->cancelar($this->relogio->agora());
                    $this->convites->save($anterior);
                }
            }
            $token = $this->tokens->gerar();
            $convite = Convite::emitir($this->convites->nextIdentity(), $lab, $input->nome, $email, Papel::from($input->papel), $token, $input->convidadoPor, $this->relogio->agora());
            $this->convites->save($convite);
            $this->entrega->enviar($convite, $token);

            return $convite->id;
        });
    }
}
