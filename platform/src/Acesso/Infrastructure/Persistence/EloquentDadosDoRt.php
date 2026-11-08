<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure\Persistence;

use Vidra\Acesso\Domain\DadosDoRt;

final readonly class EloquentDadosDoRt implements DadosDoRt
{
    public function doLaboratorio(string $id): array
    {
        $m = LaboratorioModel::query()->findOrFail($id);

        return ['nome' => (string) $m->getAttribute('rt_nome'), 'registro' => $m->getAttribute('rt_conselho').'-'.$m->getAttribute('rt_uf').' '.$m->getAttribute('rt_numero')];
    }
}
