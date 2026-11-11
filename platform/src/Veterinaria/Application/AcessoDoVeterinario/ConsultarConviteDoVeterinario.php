<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\AcessoDoVeterinario;

use Vidra\Veterinaria\Domain\Acesso\AcessoVeterinarioInvalido;
use Vidra\Veterinaria\Domain\Acesso\ConvitesDoVeterinario;
use Vidra\Veterinaria\Domain\Relogio;

final readonly class ConsultarConviteDoVeterinario
{
    public function __construct(private ConvitesDoVeterinario $convites, private Relogio $relogio) {}

    /** @return array{nome:string,email:string,laboratorio:string} */
    public function __invoke(string $token): array
    {
        $c = $this->convites->peloHash(hash('sha256', $token)) ?? throw new AcessoVeterinarioInvalido('Este convite não é válido. Peça um novo acesso ao laboratório.');
        $c->conferir($this->relogio->agora());
        if (! $this->convites->temVinculo($c->laboratorioId, $c->veterinarioId)) {
            throw new AcessoVeterinarioInvalido('Este laboratório não está mais disponível para você.');
        }
        if ($this->convites->emailDaConta($c->veterinarioId) !== null) {
            throw new AcessoVeterinarioInvalido('Você já tem acesso. Entre com sua senha.');
        }

        return [...$this->convites->pessoa($c->veterinarioId), 'laboratorio' => $this->convites->nomeDoLaboratorio($c->laboratorioId)];
    }
}
