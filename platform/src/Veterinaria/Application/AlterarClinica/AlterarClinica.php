<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\AlterarClinica;

use Vidra\Shared\Domain\Transacao;
use Vidra\Veterinaria\Application\Clinica\DadosDaClinicaInput;
use Vidra\Veterinaria\Application\Clinica\MontarDadosDaClinica;
use Vidra\Veterinaria\Application\Clinica\ObterClinica;
use Vidra\Veterinaria\Domain\Clinica\ClinicaRepository;
use Vidra\Veterinaria\Domain\Clinica\Exceptions\CnpjDeClinicaJaCadastrado;

final readonly class AlterarClinica
{
    public function __construct(
        private ClinicaRepository $clinicas,
        private ObterClinica $obter,
        private MontarDadosDaClinica $montarDados,
        private Transacao $transacao,
    ) {}

    public function __invoke(string $clinicaId, DadosDaClinicaInput $input): void
    {
        $this->transacao->executar(function () use ($clinicaId, $input): void {
            $clinica = ($this->obter)($clinicaId);
            $dados = ($this->montarDados)($input);

            $existente = $dados->cnpj === null ? null : $this->clinicas->porCnpj($dados->cnpj);
            if ($existente !== null && $existente->id->value !== $clinica->id->value) {
                throw CnpjDeClinicaJaCadastrado::para($existente->dados()->nomeFantasia);
            }

            $clinica->corrigirCadastro($dados);
            $this->clinicas->save($clinica);
        });
    }
}
