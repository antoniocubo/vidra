<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\AcessoDoVeterinario;

use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Veterinaria\Domain\Acesso\AcessoVeterinarioInvalido;
use Vidra\Veterinaria\Domain\Acesso\ConviteDoVeterinario;
use Vidra\Veterinaria\Domain\Acesso\ConvitesDoVeterinario;
use Vidra\Veterinaria\Domain\Acesso\EntregaDeAcessoVeterinario;
use Vidra\Veterinaria\Domain\Relogio;
use Vidra\Veterinaria\Domain\Vinculo\Exceptions\VinculoNaoEncontrado;
use Vidra\Veterinaria\Domain\Vinculo\VinculoId;
use Vidra\Veterinaria\Domain\Vinculo\VinculoRepository;

final readonly class ConvidarVeterinario
{
    public function __construct(private LaboratorioAtual $laboratorio, private VinculoRepository $vinculos, private ConvitesDoVeterinario $convites, private EntregaDeAcessoVeterinario $entrega, private Relogio $relogio) {}

    public function __invoke(ConvidarVeterinarioInput $input): void
    {
        $vinculo = $this->vinculos->porId(new VinculoId($input->vinculoId)) ?? throw VinculoNaoEncontrado::para();
        $laboratorio = $this->laboratorio->id();
        $token = $this->convites->token();
        $pessoa = $this->convites->transacao(function () use ($vinculo, $laboratorio, $token, $input): array {
            $id = $vinculo->veterinarioId->value;
            $pessoa = $this->convites->pessoa($id);
            if ($this->convites->emailDaConta($id) !== null) {
                throw new AcessoVeterinarioInvalido('Você já tem acesso. Entre com sua senha.');
            }
            $this->convites->salvar(ConviteDoVeterinario::criar($this->convites->nextIdentity(), $laboratorio, $id, hash('sha256', $token), $input->por, $this->relogio->agora()));

            return $pessoa;
        });
        $this->entrega->enviar($pessoa['email'], $pessoa['nome'], $this->convites->nomeDoLaboratorio($laboratorio), $token);
    }
}
