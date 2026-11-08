<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Persistence;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Vidra\Shared\Domain\ValueObjects\Cnpj;
use Vidra\Shared\Domain\ValueObjects\Email;
use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Veterinaria\Domain\Clinica\Clinica;
use Vidra\Veterinaria\Domain\Clinica\ClinicaId;
use Vidra\Veterinaria\Domain\Clinica\ClinicaRepository;
use Vidra\Veterinaria\Domain\Clinica\DadosDaClinica;
use Vidra\Veterinaria\Domain\Clinica\Exceptions\CnpjDeClinicaJaCadastrado;
use Vidra\Veterinaria\Domain\Clinica\Faturamento;
use Vidra\Veterinaria\Domain\Clinica\FormaDePagamento;

final class EloquentClinicaRepository implements ClinicaRepository
{
    public function nextIdentity(): ClinicaId
    {
        return new ClinicaId((string) Str::uuid7());
    }

    public function save(Clinica $clinica): void
    {
        $dados = $clinica->dados();
        $faturamento = $dados->faturamento;

        try {
            ClinicaModel::query()->updateOrCreate(['id' => $clinica->id->value], [
                'laboratorio_id' => $clinica->laboratorioId,
                'nome_fantasia' => $dados->nomeFantasia,
                'razao_social' => $dados->razaoSocial,
                'cnpj' => $dados->cnpj?->value,
                'cidade' => $dados->cidade,
                'bairro' => $dados->bairro,
                'email_laudos' => $dados->emailDeLaudos?->value,
                'email_faturamento' => $faturamento->email->value,
                'telefone_critico' => $dados->telefoneCritico?->digits,
                'responsavel' => $dados->responsavel,
                'observacao_coleta' => $dados->observacaoDeColeta,
                'desconto' => $faturamento->desconto,
                'dia_fechamento' => $faturamento->diaDeFechamento,
                'dia_vencimento' => $faturamento->diaDeVencimento,
                'forma_pagamento' => $faturamento->forma->value,
                'ativa' => $clinica->ativa(),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // Duas gravações simultâneas com o mesmo CNPJ: a segunda perde para o índice único.
            $existente = $dados->cnpj === null ? null : $this->porCnpj($dados->cnpj);
            if ($existente !== null) {
                throw CnpjDeClinicaJaCadastrado::para($existente->dados()->nomeFantasia);
            }
            throw $e;
        }
    }

    public function porId(ClinicaId $id): ?Clinica
    {
        $model = ClinicaModel::query()->find($id->value);

        return $model === null ? null : $this->toEntity($model);
    }

    public function porCnpj(Cnpj $cnpj): ?Clinica
    {
        $model = ClinicaModel::query()->where('cnpj', $cnpj->value)->first();

        return $model === null ? null : $this->toEntity($model);
    }

    public function porIds(array $ids): array
    {
        $clinicas = [];
        foreach (ClinicaModel::query()->whereIn('id', $ids)->get() as $model) {
            $clinicas[$model->id] = $this->toEntity($model);
        }

        return $clinicas;
    }

    public function todas(): array
    {
        return array_values(ClinicaModel::query()->orderBy('nome_fantasia')->get()->map(fn (ClinicaModel $model): Clinica => $this->toEntity($model))->all());
    }

    private function toEntity(ClinicaModel $model): Clinica
    {
        return Clinica::restaurar(
            id: new ClinicaId($model->id),
            laboratorioId: $model->laboratorio_id,
            dados: new DadosDaClinica(
                nomeFantasia: $model->nome_fantasia,
                razaoSocial: $model->razao_social,
                cnpj: $model->cnpj === null ? null : new Cnpj($model->cnpj),
                cidade: $model->cidade,
                bairro: $model->bairro,
                emailDeLaudos: $model->email_laudos === null ? null : new Email($model->email_laudos),
                telefoneCritico: $model->telefone_critico === null ? null : new Telefone($model->telefone_critico),
                responsavel: $model->responsavel,
                observacaoDeColeta: $model->observacao_coleta,
                faturamento: new Faturamento(
                    new Email($model->email_faturamento),
                    $model->desconto,
                    $model->dia_fechamento,
                    $model->dia_vencimento,
                    FormaDePagamento::from($model->forma_pagamento),
                ),
            ),
            ativa: $model->ativa,
        );
    }
}
