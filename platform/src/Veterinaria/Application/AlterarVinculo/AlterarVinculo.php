<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\AlterarVinculo;

use Vidra\Shared\Domain\Transacao;
use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Veterinaria\Domain\Clinica\ClinicaRepository;
use Vidra\Veterinaria\Domain\Clinica\Exceptions\ClinicaNaoEncontrada;
use Vidra\Veterinaria\Domain\Vinculo\Exceptions\VinculoNaoEncontrado;
use Vidra\Veterinaria\Domain\Vinculo\QuemPaga;
use Vidra\Veterinaria\Domain\Vinculo\RecebeLaudo;
use Vidra\Veterinaria\Domain\Vinculo\VinculoId;
use Vidra\Veterinaria\Domain\Vinculo\VinculoRepository;

/** Muda só o que é do laboratório: pagador, telefone crítico, laudos e onde atende. */
final readonly class AlterarVinculo
{
    public function __construct(private VinculoRepository $vinculos, private ClinicaRepository $clinicas, private Transacao $transacao) {}

    public function __invoke(AlterarVinculoInput $input): void
    {
        $this->transacao->executar(function () use ($input): void {
            $vinculo = $this->vinculos->porId(new VinculoId($input->vinculoId)) ?? throw VinculoNaoEncontrado::para();
            $clinicaIds = array_values(array_unique($input->clinicaIds));

            if (count($this->clinicas->porIds($clinicaIds)) !== count($clinicaIds)) {
                throw ClinicaNaoEncontrada::para();
            }

            $this->vinculos->save($vinculo->redefinirPreferencias(
                QuemPaga::from($input->quemPaga),
                new Telefone($input->telefoneCritico),
                $input->vePedidosDosColegas,
                RecebeLaudo::from($input->recebeLaudo),
                $clinicaIds,
            ));
        });
    }
}
