<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application\RegistrarLaboratorio;

use Vidra\Acesso\Domain\Administrador;
use Vidra\Acesso\Domain\CnpjJaCadastrado;
use Vidra\Acesso\Domain\Cobranca;
use Vidra\Acesso\Domain\EmailJaCadastrado;
use Vidra\Acesso\Domain\Endereco;
use Vidra\Acesso\Domain\Laboratorio;
use Vidra\Acesso\Domain\LaboratorioId;
use Vidra\Acesso\Domain\LaboratorioRepository;
use Vidra\Acesso\Domain\LinhaDeProduto;
use Vidra\Acesso\Domain\Plano;
use Vidra\Acesso\Domain\RegistroProfissional;
use Vidra\Acesso\Domain\Relogio;
use Vidra\Acesso\Domain\ResponsavelTecnico;
use Vidra\Shared\Domain\ValueObjects\Cnpj;
use Vidra\Shared\Domain\ValueObjects\Email;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Shared\Domain\ValueObjects\Uf;

final readonly class RegistrarLaboratorio
{
    public function __construct(private LaboratorioRepository $laboratorios, private Relogio $relogio) {}

    public function __invoke(RegistrarLaboratorioInput $input): LaboratorioId
    {
        $cnpj = new Cnpj($input->cnpj);
        $email = new Email($input->email);
        if ($this->laboratorios->existeCnpj($cnpj)) {
            throw CnpjJaCadastrado::para($cnpj);
        }
        if ($this->laboratorios->existeEmail($email)) {
            throw EmailJaCadastrado::para($email);
        }
        if (! $input->souRt && $input->rtEmail === null) {
            throw InvalidValue::for('rt_email', 'informe o e-mail do responsável técnico');
        }
        $administrador = new Administrador($input->nome, $email, new Telefone($input->celular), $input->senha, $input->souRt);
        $laboratorio = Laboratorio::registrar(
            $this->laboratorios->nextIdentity(), $input->laboratorioNome, $cnpj, new Telefone($input->telefone),
            new Endereco($input->cep, $input->cidade, Uf::from($input->uf), $input->endereco),
            LinhaDeProduto::from($input->linha), Plano::from($input->plano), Cobranca::from($input->cobranca),
            new ResponsavelTecnico($input->rtNome, new RegistroProfissional($input->rtConselho, $input->rtNumero, Uf::from($input->rtUf)), $input->rtTitulo, $input->souRt ? null : new Email($input->rtEmail ?? '')),
            $this->relogio->agora(),
        );
        // TODO: convidar o RT por e-mail quando o fluxo de convites estiver disponível.
        $this->laboratorios->save($laboratorio, $administrador);

        return $laboratorio->id;
    }
}
