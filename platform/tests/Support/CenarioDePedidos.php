<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Gravações diretas para os testes de HTTP dos pedidos: tutor, animal, veterinário, clínica e exames de um laboratório. */
final class CenarioDePedidos
{
    /** @return array{animal: string, tutor: string, vinculo: string, clinica: string, bioquimico: string, hemograma: string} */
    public static function criar(string $laboratorio, string $nomeDoAnimal = 'Thor', string $celular = '19998124410'): array
    {
        $db = DB::connection(config()->string('vidra.conexao_sistema'));
        $agora = now();
        $ids = array_map(fn (): string => (string) Str::uuid7(), array_flip(['animal', 'tutor', 'veterinario', 'vinculo', 'clinica', 'bioquimico', 'hemograma']));

        $db->table('tutores')->insert(['id' => $ids['tutor'], 'laboratorio_id' => $laboratorio, 'nome' => 'Carla Mendes', 'celular' => $celular, 'email' => 'carla@exemplo.com.br', 'created_at' => $agora, 'updated_at' => $agora]);
        $db->table('animais')->insert([
            'id' => $ids['animal'], 'laboratorio_id' => $laboratorio, 'tutor_id' => $ids['tutor'], 'nome' => $nomeDoAnimal, 'especie' => 'cao', 'raca' => 'Golden Retriever',
            'sexo' => 'macho', 'nascimento' => '2019-03-01', 'nascimento_estimado' => false, 'castrado' => 'sim', 'created_at' => $agora, 'updated_at' => $agora,
        ]);
        $numero = (string) random_int(100000, 999999);
        $db->table('veterinarios')->insert([
            'id' => $ids['veterinario'], 'tratamento' => 'dr', 'nome' => 'Rafael Lima', 'crmv_numero' => $numero, 'crmv_uf' => 'SP', 'email' => "rafael{$numero}@exemplo.com.br",
            'celular' => '19999990000', 'created_at' => $agora, 'updated_at' => $agora,
        ]);
        $db->table('laboratorio_veterinario')->insert([
            'id' => $ids['vinculo'], 'laboratorio_id' => $laboratorio, 'veterinario_id' => $ids['veterinario'], 'quem_paga' => 'clinica', 'telefone_critico' => '1932410099',
            've_pedidos_dos_colegas' => false, 'recebe_laudo' => 'cada_laudo', 'created_at' => $agora, 'updated_at' => $agora,
        ]);
        $db->table('clinicas')->insert([
            'id' => $ids['clinica'], 'laboratorio_id' => $laboratorio, 'nome_fantasia' => 'Clínica Patas', 'cidade' => 'Campinas', 'email_faturamento' => 'financeiro@patas.com.br',
            'desconto' => 10, 'dia_fechamento' => 31, 'dia_vencimento' => 10, 'forma_pagamento' => 'pix', 'ativa' => true, 'created_at' => $agora, 'updated_at' => $agora,
        ]);
        $db->table('clinica_veterinario')->insert(['laboratorio_id' => $laboratorio, 'clinica_id' => $ids['clinica'], 'vinculo_id' => $ids['vinculo'], 'created_at' => $agora, 'updated_at' => $agora]);
        foreach ([['bioquimico', 'BIOQ', 'Bioquímico renal e hepático', 'bioquimica', 'Soro', 10667], ['hemograma', 'HEMO', 'Hemograma completo', 'hematologia', 'Sangue EDTA', 4667]] as [$chave, $codigo, $nome, $setor, $material, $preco]) {
            $db->table('exames')->insert([
                'id' => $ids[$chave], 'laboratorio_id' => $laboratorio, 'codigo' => $codigo, 'nome' => $nome, 'setor' => $setor, 'material' => $material,
                'prazo_horas' => 24, 'preco_centavos' => $preco, 'ativo' => true, 'created_at' => $agora, 'updated_at' => $agora,
            ]);
        }

        return ['animal' => $ids['animal'], 'tutor' => $ids['tutor'], 'vinculo' => $ids['vinculo'], 'clinica' => $ids['clinica'], 'bioquimico' => $ids['bioquimico'], 'hemograma' => $ids['hemograma']];
    }
}
