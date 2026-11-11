<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application;

use Vidra\Acesso\Domain\ContasDeUsuario;
use Vidra\Acesso\Domain\ConviteInvalido;
use Vidra\Acesso\Domain\ConviteRepository;
use Vidra\Acesso\Domain\Equipe;
use Vidra\Acesso\Domain\Relogio;

final readonly class ConsultarConvite
{
    public function __construct(private ConviteRepository $convites, private Equipe $equipe, private ContasDeUsuario $contas, private Relogio $relogio) {}

    /** @return array{nome:string,email:string,papel:string,laboratorio:string,temConta:bool} */
    public function __invoke(string $token): array
    {
        $convite = $this->convites->findByTokenHash(hash('sha256', $token)) ?? throw ConviteInvalido::para();
        $convite->conferirValidade($this->relogio->agora());

        return ['nome' => $convite->nome, 'email' => $convite->email, 'papel' => $convite->papel->rotulo(), 'laboratorio' => $this->equipe->nomeDoLaboratorio($convite->laboratorioId), 'temConta' => $this->contas->existe($convite->email)];
    }
}
