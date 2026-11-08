<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Pedidos;

use Vidra\Pedidos\Domain\Portas\TextosDaLinha;
use Vidra\Pedidos\Domain\Portas\Vocabulario;

final readonly class VocabularioVeterinaria implements Vocabulario
{
    public function textos(): TextosDaLinha
    {
        return new TextosDaLinha(
            paciente: 'Animal',
            responsavel: 'Tutor',
            solicitante: 'Veterinário solicitante',
            secaoDoPaciente: 'Tutor e animal',
            buscaDoPaciente: 'Buscar por telefone, CPF, nome do tutor ou do animal',
            pagadorSolicitante: 'O veterinário',
            pagadorResponsavel: 'O tutor',
            novoSolicitante: '+ Novo veterinário',
            novoPaciente: '+ Novo tutor ou animal',
        );
    }
}
