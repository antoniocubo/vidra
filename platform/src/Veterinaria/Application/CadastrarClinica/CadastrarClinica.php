<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\CadastrarClinica;

use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Shared\Domain\Transacao;
use Vidra\Veterinaria\Application\Clinica\DadosDaClinicaInput;
use Vidra\Veterinaria\Application\Clinica\MontarDadosDaClinica;
use Vidra\Veterinaria\Domain\Clinica\Clinica;
use Vidra\Veterinaria\Domain\Clinica\ClinicaId;
use Vidra\Veterinaria\Domain\Clinica\ClinicaRepository;
use Vidra\Veterinaria\Domain\Clinica\Exceptions\CnpjDeClinicaJaCadastrado;

final readonly class CadastrarClinica
{
    public function __construct(
        private ClinicaRepository $clinicas,
        private LaboratorioAtual $laboratorio,
        private MontarDadosDaClinica $montarDados,
        private Transacao $transacao,
    ) {}

    public function __invoke(DadosDaClinicaInput $input): ClinicaId
    {
        return $this->transacao->executar(function () use ($input): ClinicaId {
            $dados = ($this->montarDados)($input);

            if ($dados->cnpj !== null && ($existente = $this->clinicas->porCnpj($dados->cnpj)) !== null) {
                throw CnpjDeClinicaJaCadastrado::para($existente->dados()->nomeFantasia);
            }

            $clinica = Clinica::registrar($this->clinicas->nextIdentity(), $this->laboratorio->id(), $dados);
            $this->clinicas->save($clinica);

            return $clinica->id;
        });
    }
}
