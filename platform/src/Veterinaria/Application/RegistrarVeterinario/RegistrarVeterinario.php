<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\RegistrarVeterinario;

use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Shared\Domain\Transacao;
use Vidra\Shared\Domain\ValueObjects\Email;
use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Shared\Domain\ValueObjects\Uf;
use Vidra\Veterinaria\Domain\Clinica\ClinicaRepository;
use Vidra\Veterinaria\Domain\Clinica\Exceptions\ClinicaNaoEncontrada;
use Vidra\Veterinaria\Domain\Veterinario\Crmv;
use Vidra\Veterinaria\Domain\Veterinario\Tratamento;
use Vidra\Veterinaria\Domain\Veterinario\Veterinario;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioRepository;
use Vidra\Veterinaria\Domain\Vinculo\Exceptions\VeterinarioJaVinculado;
use Vidra\Veterinaria\Domain\Vinculo\QuemPaga;
use Vidra\Veterinaria\Domain\Vinculo\RecebeLaudo;
use Vidra\Veterinaria\Domain\Vinculo\Vinculo;
use Vidra\Veterinaria\Domain\Vinculo\VinculoRepository;

final readonly class RegistrarVeterinario
{
    public function __construct(
        private VeterinarioRepository $veterinarios,
        private LaboratorioAtual $laboratorio,
        private VinculoRepository $vinculos,
        private Transacao $transacao,
        private ClinicaRepository $clinicas,
    ) {}

    public function __invoke(RegistrarVeterinarioInput $input): RegistrarVeterinarioResultado
    {
        return $this->transacao->executar(function () use ($input): RegistrarVeterinarioResultado {
            $laboratorioId = $this->laboratorio->id();
            $clinicaIds = array_values(array_unique($input->clinicaIds));
            if (count($this->clinicas->porIds($clinicaIds)) !== count($clinicaIds)) {
                throw ClinicaNaoEncontrada::para();
            }
            $crmv = new Crmv($input->crmvNumero, Uf::from($input->crmvUf));
            $veterinario = $this->veterinarios->findByCrmv($crmv);
            $reaproveitado = $veterinario !== null;

            if ($veterinario !== null && $this->vinculos->existe($veterinario->id)) {
                throw VeterinarioJaVinculado::para($crmv);
            }

            if ($veterinario === null) {
                $veterinario = Veterinario::registrar(
                    id: $this->veterinarios->nextIdentity(),
                    tratamento: Tratamento::from($input->tratamento),
                    nome: $input->nome,
                    crmv: $crmv,
                    email: new Email($input->email),
                    celular: new Telefone($input->celular),
                    especialidade: $input->especialidade,
                );
                $this->veterinarios->save($veterinario);
            }

            $vinculo = Vinculo::registrar(
                id: $this->vinculos->nextIdentity(),
                laboratorioId: $laboratorioId,
                veterinarioId: $veterinario->id,
                quemPaga: QuemPaga::from($input->quemPaga),
                telefoneCritico: new Telefone($input->telefoneCritico),
                vePedidosDosColegas: $input->vePedidosDosColegas,
                recebeLaudo: RecebeLaudo::from($input->recebeLaudo),
                clinicaIds: $clinicaIds,
            );
            $this->vinculos->save($vinculo);

            return new RegistrarVeterinarioResultado($veterinario->id, $reaproveitado, $veterinario->nomeDeExibicao(), $vinculo->id);
        });
    }
}
