<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\ReativarClinica;

use Vidra\Shared\Domain\Transacao;
use Vidra\Veterinaria\Application\Clinica\ObterClinica;
use Vidra\Veterinaria\Domain\Clinica\ClinicaRepository;

final readonly class ReativarClinica
{
    public function __construct(private ClinicaRepository $clinicas, private ObterClinica $obter, private Transacao $transacao) {}

    public function __invoke(string $clinicaId): void
    {
        $this->transacao->executar(function () use ($clinicaId): void {
            $clinica = ($this->obter)($clinicaId);
            $clinica->reativar();
            $this->clinicas->save($clinica);
        });
    }
}
