<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\ListarVeterinarios;

/** O veterinário como o laboratório o enxerga: dados da pessoa mais as preferências do vínculo. */
final readonly class VeterinarioDto
{
    /** @param list<ClinicaDoVeterinarioDto> $clinicas */
    public function __construct(
        public string $vinculoId,
        public string $nome,
        public string $nomeCompleto,
        public ?string $especialidade,
        public string $crmv,
        public string $email,
        public string $celular,
        public string $telefoneCritico,
        public string $quemPaga,
        public string $recebeLaudo,
        public bool $vePedidosDosColegas,
        public array $clinicas,
    ) {}
}
