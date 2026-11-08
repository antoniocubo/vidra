<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

/**
 * @property string $id
 * @property string $laboratorio_id
 * @property string $nome_fantasia
 * @property string|null $razao_social
 * @property string|null $cnpj
 * @property string $cidade
 * @property string|null $bairro
 * @property string|null $email_laudos
 * @property string $email_faturamento
 * @property string|null $telefone_critico
 * @property string|null $responsavel
 * @property string|null $observacao_coleta
 * @property int $desconto
 * @property int $dia_fechamento
 * @property int $dia_vencimento
 * @property string $forma_pagamento
 * @property bool $ativa
 */
final class ClinicaModel extends Model
{
    use HasUuids;
    use PertenceAoLaboratorio;

    protected $table = 'clinicas';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['ativa' => 'boolean', 'desconto' => 'integer', 'dia_fechamento' => 'integer', 'dia_vencimento' => 'integer'];
    }
}
