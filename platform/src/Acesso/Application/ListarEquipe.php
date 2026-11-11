<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application;

use Vidra\Acesso\Domain\Convite;
use Vidra\Acesso\Domain\ConviteRepository;
use Vidra\Acesso\Domain\Equipe;
use Vidra\Acesso\Domain\Papel;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class ListarEquipe
{
    public function __construct(private Equipe $equipe, private ConviteRepository $convites, private LaboratorioAtual $laboratorio) {}

    /** @return array{membros:list<\Vidra\Acesso\Domain\MembroDaEquipe>,convites:list<array{id:string,nome:string,email:string,papel:string,expiraEm:string}>} */
    public function __invoke(): array
    {
        $lab = $this->laboratorio->id();

        return ['membros' => $this->equipe->listar($lab), 'convites' => array_map(static fn (Convite $c): array => ['id' => $c->id, 'nome' => $c->nome, 'email' => $c->email, 'papel' => $c->papel->value, 'expiraEm' => $c->expiraEm->format(DATE_ATOM)], $this->convites->pendentes($lab))];
    }
}
