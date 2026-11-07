<?php

declare(strict_types=1);

namespace Tests\Support;

use Vidra\Acesso\Application\RegistrarLaboratorio\RegistrarLaboratorioInput;

final class DadosCadastro
{
    /** @return array<string, mixed> */
    public static function validos(): array
    {
        return [
            'laboratorio_nome' => 'Laboratório Alfa Vet', 'cnpj' => '11.222.333/0001-81', 'telefone' => '(19) 3242-7180',
            'cep' => '13015-001', 'cidade' => 'Campinas', 'uf' => 'SP', 'endereco' => 'Rua Barão de Jaguara, 1481',
            'linha' => 'pet', 'plano' => 'laboratorio', 'cobranca' => 'mensal', 'sou_rt' => true,
            'rt_nome' => 'Marina Alves', 'rt_numero' => '31477', 'rt_uf' => 'SP', 'rt_titulo' => 'Patologista', 'rt_email' => null,
            'nome' => 'Marina Alves', 'email' => 'marina@alfavet.com.br', 'celular' => '(19) 99127-4410', 'senha' => 'uma-frase-segura-2026', 'termos' => true,
        ];
    }

    public static function input(string $cnpj = '11.222.333/0001-81', string $email = 'marina@alfavet.com.br'): RegistrarLaboratorioInput
    {
        return new RegistrarLaboratorioInput('Laboratório Alfa Vet', $cnpj, '(19) 3242-7180', '13015-001', 'Campinas', 'SP', 'Rua Barão de Jaguara, 1481', 'pet', 'laboratorio', 'mensal', 'Marina Alves', 'CONSELHO', '31477', 'SP', null, null, true, 'Marina Alves', $email, '(19) 99127-4410', 'uma-frase-segura-2026');
    }
}
