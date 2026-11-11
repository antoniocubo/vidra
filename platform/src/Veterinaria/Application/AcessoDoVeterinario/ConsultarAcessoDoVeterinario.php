<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\AcessoDoVeterinario;

use Vidra\Veterinaria\Domain\Acesso\ConvitesDoVeterinario;
use Vidra\Veterinaria\Domain\Vinculo\Exceptions\VinculoNaoEncontrado;
use Vidra\Veterinaria\Domain\Vinculo\VinculoId;
use Vidra\Veterinaria\Domain\Vinculo\VinculoRepository;

final readonly class ConsultarAcessoDoVeterinario
{
    public function __construct(private VinculoRepository $vinculos, private ConvitesDoVeterinario $convites) {}

    public function __invoke(string $vinculo): ?string
    {
        $v = $this->vinculos->porId(new VinculoId($vinculo)) ?? throw VinculoNaoEncontrado::para();

        return $this->convites->emailDaConta($v->veterinarioId->value);
    }
}
