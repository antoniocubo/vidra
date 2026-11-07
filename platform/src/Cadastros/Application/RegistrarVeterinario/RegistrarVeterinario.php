<?php

declare(strict_types=1);

namespace Vidra\Cadastros\Application\RegistrarVeterinario;

use Vidra\Cadastros\Domain\Veterinario\Crmv;
use Vidra\Cadastros\Domain\Veterinario\Exceptions\CrmvJaCadastrado;
use Vidra\Cadastros\Domain\Veterinario\Tratamento;
use Vidra\Cadastros\Domain\Veterinario\Veterinario;
use Vidra\Cadastros\Domain\Veterinario\VeterinarioId;
use Vidra\Cadastros\Domain\Veterinario\VeterinarioRepository;
use Vidra\Shared\Domain\ValueObjects\Email;
use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Shared\Domain\ValueObjects\Uf;

final readonly class RegistrarVeterinario
{
    public function __construct(private VeterinarioRepository $veterinarios) {}

    /**
     * @throws CrmvJaCadastrado quando o CRMV já existe no Vidra
     */
    public function __invoke(RegistrarVeterinarioInput $input): VeterinarioId
    {
        $crmv = new Crmv($input->crmvNumero, Uf::from($input->crmvUf));

        $existente = $this->veterinarios->findByCrmv($crmv);
        if ($existente !== null) {
            throw CrmvJaCadastrado::para($crmv, $existente->id);
        }

        $veterinario = Veterinario::registrar(
            id: $this->veterinarios->nextIdentity(),
            tratamento: Tratamento::from($input->tratamento),
            nome: $input->nome,
            crmv: $crmv,
            email: new Email($input->email),
            celular: new Telefone($input->celular),
            telefoneCritico: new Telefone($input->telefoneCritico),
            especialidade: $input->especialidade,
        );

        $this->veterinarios->save($veterinario);

        return $veterinario->id;
    }
}
