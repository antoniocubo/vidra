<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Persistence;

use Illuminate\Support\Str;
use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Veterinaria\Domain\Clinica\ClinicaId;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioId;
use Vidra\Veterinaria\Domain\Vinculo\QuemPaga;
use Vidra\Veterinaria\Domain\Vinculo\RecebeLaudo;
use Vidra\Veterinaria\Domain\Vinculo\Vinculo;
use Vidra\Veterinaria\Domain\Vinculo\VinculoId;
use Vidra\Veterinaria\Domain\Vinculo\VinculoRepository;

final class EloquentVinculoRepository implements \Vidra\Veterinaria\Domain\Vinculo\VinculosParaEscolha, VinculoRepository
{
    public function nomes(): array
    {
        $vinculos = VinculoModel::query()->get(['id', 'veterinario_id']);
        $veterinarios = VeterinarioModel::query()->whereIn('id', $vinculos->pluck('veterinario_id'))->orderBy('nome')->get()->keyBy('id');
        $nomes = [];
        foreach ($vinculos as $vinculo) {
            $vet = $veterinarios->get($vinculo->getAttribute('veterinario_id'));
            if ($vet !== null) {
                $nomes[(string) $vinculo->getAttribute('id')] = \Vidra\Veterinaria\Domain\Veterinario\Tratamento::from($vet->tratamento)->prefixo().$vet->nome;
            }
        }
        asort($nomes);

        return $nomes;
    }

    public function nextIdentity(): VinculoId
    {
        return new VinculoId((string) Str::uuid7());
    }

    public function save(Vinculo $vinculo): void
    {
        VinculoModel::query()->updateOrCreate(['id' => $vinculo->id->value], [
            'laboratorio_id' => $vinculo->laboratorioId,
            'veterinario_id' => $vinculo->veterinarioId->value,
            'quem_paga' => $vinculo->quemPaga->value,
            'telefone_critico' => $vinculo->telefoneCritico->digits,
            've_pedidos_dos_colegas' => $vinculo->vePedidosDosColegas,
            'recebe_laudo' => $vinculo->recebeLaudo->value,
        ]);
        $this->gravarClinicas($vinculo);
    }

    public function existe(VeterinarioId $veterinarioId): bool
    {
        return VinculoModel::query()->where('veterinario_id', $veterinarioId->value)->exists();
    }

    public function porId(VinculoId $id): ?Vinculo
    {
        $model = VinculoModel::query()->find($id->value);

        return $model === null ? null : $this->toEntity($model, $this->clinicasDe([$model->id]));
    }

    public function todos(): array
    {
        $models = VinculoModel::query()->get();
        $clinicas = $this->clinicasDe(array_values(array_map(fn (VinculoModel $model): string => $model->id, $models->all())));

        return array_values($models->map(fn (VinculoModel $model): Vinculo => $this->toEntity($model, $clinicas))->all());
    }

    public function daClinica(ClinicaId $clinicaId): array
    {
        $ids = ClinicaVeterinarioModel::query()->where('clinica_id', $clinicaId->value)->pluck('vinculo_id')->all();
        $models = VinculoModel::query()->whereIn('id', $ids)->get();
        $clinicas = $this->clinicasDe(array_values(array_map(fn (VinculoModel $model): string => $model->id, $models->all())));

        return array_values($models->map(fn (VinculoModel $model): Vinculo => $this->toEntity($model, $clinicas))->all());
    }

    private function gravarClinicas(Vinculo $vinculo): void
    {
        $atuais = ClinicaVeterinarioModel::query()->where('vinculo_id', $vinculo->id->value);
        (clone $atuais)->whereNotIn('clinica_id', $vinculo->clinicaIds)->delete();
        $jaGravadas = $atuais->pluck('clinica_id')->all();

        foreach (array_diff($vinculo->clinicaIds, $jaGravadas) as $clinicaId) {
            ClinicaVeterinarioModel::query()->create([
                'laboratorio_id' => $vinculo->laboratorioId,
                'clinica_id' => $clinicaId,
                'vinculo_id' => $vinculo->id->value,
            ]);
        }
    }

    /**
     * @param  list<string>  $vinculoIds
     * @return array<string, list<string>> ids das clínicas de cada vínculo
     */
    private function clinicasDe(array $vinculoIds): array
    {
        $porVinculo = [];
        $ligacoes = ClinicaVeterinarioModel::query()->whereIn('vinculo_id', $vinculoIds)->orderBy('id')->get(['vinculo_id', 'clinica_id']);
        foreach ($ligacoes as $ligacao) {
            $porVinculo[$ligacao->vinculo_id][] = $ligacao->clinica_id;
        }

        return $porVinculo;
    }

    /** @param array<string, list<string>> $clinicas */
    private function toEntity(VinculoModel $model, array $clinicas): Vinculo
    {
        return Vinculo::restaurar(
            id: new VinculoId($model->id),
            laboratorioId: $model->laboratorio_id,
            veterinarioId: new VeterinarioId($model->veterinario_id),
            quemPaga: QuemPaga::from($model->quem_paga),
            telefoneCritico: new Telefone($model->telefone_critico),
            vePedidosDosColegas: $model->ve_pedidos_dos_colegas,
            recebeLaudo: RecebeLaudo::from($model->recebe_laudo),
            clinicaIds: $clinicas[$model->id] ?? [],
        );
    }
}
