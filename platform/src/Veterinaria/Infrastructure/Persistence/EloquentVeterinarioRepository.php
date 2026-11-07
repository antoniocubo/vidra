<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Persistence;

use Illuminate\Support\Str;
use Vidra\Shared\Domain\ValueObjects\Email;
use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Shared\Domain\ValueObjects\Uf;
use Vidra\Veterinaria\Domain\Veterinario\Crmv;
use Vidra\Veterinaria\Domain\Veterinario\Tratamento;
use Vidra\Veterinaria\Domain\Veterinario\Veterinario;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioId;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioRepository;

final class EloquentVeterinarioRepository implements VeterinarioRepository
{
    public function nextIdentity(): VeterinarioId
    {
        return new VeterinarioId((string) Str::uuid7());
    }

    public function save(Veterinario $veterinario): void
    {
        VeterinarioModel::query()->updateOrCreate(
            ['id' => $veterinario->id->value],
            [
                'tratamento' => $veterinario->tratamento()->value,
                'nome' => $veterinario->nome(),
                'crmv_numero' => $veterinario->crmv->numero,
                'crmv_uf' => $veterinario->crmv->uf->value,
                'email' => $veterinario->email()->value,
                'celular' => $veterinario->celular()->digits,
                'telefone_critico' => $veterinario->telefoneCritico()->digits,
                'especialidade' => $veterinario->especialidade(),
            ],
        );
    }

    public function findByCrmv(Crmv $crmv): ?Veterinario
    {
        $model = VeterinarioModel::query()
            ->where('crmv_numero', $crmv->numero)
            ->where('crmv_uf', $crmv->uf->value)
            ->first();

        return $model === null ? null : $this->toEntity($model);
    }

    private function toEntity(VeterinarioModel $model): Veterinario
    {
        return Veterinario::restaurar(
            id: new VeterinarioId($model->id),
            tratamento: Tratamento::from($model->tratamento),
            nome: $model->nome,
            crmv: new Crmv($model->crmv_numero, Uf::from($model->crmv_uf)),
            email: new Email($model->email),
            celular: new Telefone($model->celular),
            telefoneCritico: new Telefone($model->telefone_critico),
            especialidade: $model->especialidade,
        );
    }
}
