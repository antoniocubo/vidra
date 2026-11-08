<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Infrastructure\Persistence;

use Illuminate\Support\Str;
use LogicException;
use Vidra\Catalogo\Domain\Exame\Analito;
use Vidra\Catalogo\Domain\Exame\AnalitoId;
use Vidra\Catalogo\Domain\Exame\CodigoDoExame;
use Vidra\Catalogo\Domain\Exame\Decimal;
use Vidra\Catalogo\Domain\Exame\Exame;
use Vidra\Catalogo\Domain\Exame\ExameId;
use Vidra\Catalogo\Domain\Exame\ExameRepository;
use Vidra\Catalogo\Domain\Exame\FaixaDeReferencia;
use Vidra\Catalogo\Domain\Exame\Setor;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class EloquentExameRepository implements ExameRepository
{
    public function __construct(private LaboratorioAtual $laboratorio) {}

    public function nextIdentity(): ExameId
    {
        return new ExameId((string) Str::uuid7());
    }

    public function nextAnalitoIdentity(): AnalitoId
    {
        return new AnalitoId((string) Str::uuid7());
    }

    public function save(Exame $exame): void
    {
        if ($exame->laboratorioId !== $this->laboratorio->id()) {
            throw new LogicException('Laboratório incompatível com o agregado.');
        }
        ExameModel::query()->updateOrCreate(['id' => $exame->id->value], ['laboratorio_id' => $exame->laboratorioId, 'codigo' => $exame->codigo->value, 'nome' => $exame->nome, 'setor' => $exame->setor->value, 'material' => $exame->material, 'metodo' => $exame->metodo, 'prazo_horas' => $exame->prazoHoras, 'preco_centavos' => $exame->precoCentavos, 'ativo' => $exame->ativo]);
        $ids = array_map(fn (Analito $a): string => $a->id->value, $exame->analitos);
        AnalitoModel::query()->where('exame_id', $exame->id->value)->whereNotIn('id', $ids)->delete();
        foreach ($exame->analitos as $ordem => $a) {
            AnalitoModel::query()->updateOrCreate(['id' => $a->id->value, 'exame_id' => $exame->id->value], ['laboratorio_id' => $exame->laboratorioId, 'nome' => $a->nome, 'unidade' => $a->unidade, 'casas_decimais' => $a->casasDecimais, 'ordem' => $ordem]);
            $perfis = array_map(fn (FaixaDeReferencia $f): string => $f->perfil, $a->faixas);
            FaixaReferenciaModel::query()->where('analito_id', $a->id->value)->whereNotIn('perfil', $perfis)->delete();
            foreach ($a->faixas as $f) {
                FaixaReferenciaModel::query()->updateOrCreate(['analito_id' => $a->id->value, 'perfil' => $f->perfil], ['laboratorio_id' => $exame->laboratorioId, 'minimo' => $f->minimo?->value, 'maximo' => $f->maximo?->value, 'critico_baixo' => $f->criticoBaixo?->value, 'critico_alto' => $f->criticoAlto?->value]);
            }
        }
    }

    public function porId(ExameId $id): ?Exame
    {
        $model = ExameModel::query()->find($id->value);

        return $model !== null ? $this->restaurar($model) : null;
    }

    public function existeCodigo(CodigoDoExame $codigo, ?ExameId $exceto = null): ?Exame
    {
        $query = ExameModel::query()->where('codigo', $codigo->value);
        if ($exceto !== null) {
            $query->where('id', '!=', $exceto->value);
        }
        $model = $query->first();

        return $model !== null ? $this->restaurar($model) : null;
    }

    public function listar(string $busca = '', ?Setor $setor = null, bool $mostrarInativos = false): array
    {
        $query = ExameModel::query();
        if (! $mostrarInativos) {
            $query->where('ativo', true);
        }
        if ($setor !== null) {
            $query->where('setor', $setor->value);
        }
        if (trim($busca) !== '') {
            $termo = '%'.mb_strtolower(trim($busca)).'%';
            $query->where(fn ($q) => $q->whereRaw('LOWER(codigo) LIKE ?', [$termo])->orWhereRaw('LOWER(nome) LIKE ?', [$termo]));
        }

        return array_values($query->orderBy('nome')->get()->map(fn (ExameModel $m): Exame => $this->restaurar($m))->all());
    }

    private function restaurar(ExameModel $m): Exame
    {
        $analitos = [];
        foreach (AnalitoModel::query()->where('exame_id', $m->id)->orderBy('ordem')->get() as $a) {
            $faixas = [];
            foreach (FaixaReferenciaModel::query()->where('analito_id', $a->id)->orderBy('perfil')->get() as $f) {
                $faixas[] = new FaixaDeReferencia($f->perfil, self::decimal($f->minimo), self::decimal($f->maximo), self::decimal($f->critico_baixo), self::decimal($f->critico_alto));
            }
            $analitos[] = new Analito(new AnalitoId($a->id), $a->nome, $a->unidade, $a->casas_decimais, $faixas);
        }

        return Exame::restaurar(new ExameId($m->id), $m->laboratorio_id, new CodigoDoExame($m->codigo), $m->nome, Setor::from($m->setor), $m->material, $m->metodo, $m->prazo_horas, $m->preco_centavos, $m->ativo, $analitos);
    }

    private static function decimal(?string $valor): ?Decimal
    {
        return $valor === null ? null : new Decimal($valor);
    }
}
