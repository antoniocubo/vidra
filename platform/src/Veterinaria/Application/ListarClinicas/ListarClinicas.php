<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\ListarClinicas;

use Vidra\Veterinaria\Application\Busca\TextoDeBusca;
use Vidra\Veterinaria\Application\Clinica\ClinicaDto;
use Vidra\Veterinaria\Domain\Clinica\Clinica;
use Vidra\Veterinaria\Domain\Clinica\ClinicaRepository;

final readonly class ListarClinicas
{
    public function __construct(private ClinicaRepository $clinicas) {}

    /** @return list<ClinicaDto> por nome; as inativas só entram quando pedidas */
    public function __invoke(ListarClinicasInput $input = new ListarClinicasInput): array
    {
        $clinicas = array_filter($this->clinicas->todas(), fn (Clinica $c): bool => ($input->incluirInativas || $c->ativa()) && $this->casa($c, $input->busca));
        usort($clinicas, fn (Clinica $a, Clinica $b): int => strcmp(TextoDeBusca::normalizar($a->dados()->nomeFantasia), TextoDeBusca::normalizar($b->dados()->nomeFantasia)));

        return array_map(fn (Clinica $c): ClinicaDto => ClinicaDto::de($c), $clinicas);
    }

    private function casa(Clinica $clinica, string $busca): bool
    {
        $dados = $clinica->dados();

        return TextoDeBusca::casa($busca, [$dados->nomeFantasia, $dados->cnpj?->value, $dados->cidade]);
    }
}
