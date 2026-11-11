<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\AcessoDoVeterinario;

use Vidra\Veterinaria\Domain\Acesso\AcessoVeterinarioInvalido;
use Vidra\Veterinaria\Domain\Acesso\ConvitesDoVeterinario;
use Vidra\Veterinaria\Domain\Relogio;

final readonly class AceitarConviteDoVeterinario
{
    public function __construct(private ConvitesDoVeterinario $convites, private ConsultarConviteDoVeterinario $consultar, private Relogio $relogio) {}

    /** @return array{contaId:int,laboratorioId:string} */
    public function __invoke(AceitarConviteDoVeterinarioInput $input): array
    {
        if (mb_strlen($input->senha) < 10) {
            throw new AcessoVeterinarioInvalido('Escolha uma senha com pelo menos 10 caracteres.');
        }

        return $this->convites->transacao(function () use ($input): array {
            $pessoa = ($this->consultar)($input->token);
            $c = $this->convites->peloHash(hash('sha256', $input->token)) ?? throw new AcessoVeterinarioInvalido('Este convite não é válido.');
            $id = $this->convites->criarConta($c->veterinarioId, $pessoa['email'], $input->senha);
            $this->convites->aceitar($c->id, $this->relogio->agora());

            return ['contaId' => $id, 'laboratorioId' => $c->laboratorioId];
        });
    }
}
