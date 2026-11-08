<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Persistence;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Vidra\Shared\Domain\ValueObjects\Email;
use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Veterinaria\Domain\Tutor\AutorizacaoDeEmail;
use Vidra\Veterinaria\Domain\Tutor\Cpf;
use Vidra\Veterinaria\Domain\Tutor\Exceptions\CelularJaCadastrado;
use Vidra\Veterinaria\Domain\Tutor\Tutor;
use Vidra\Veterinaria\Domain\Tutor\TutorId;
use Vidra\Veterinaria\Domain\Tutor\TutorRepository;

final class EloquentTutorRepository implements TutorRepository
{
    public function nextIdentity(): TutorId
    {
        return new TutorId((string) Str::uuid7());
    }

    public function save(Tutor $tutor): void
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(fn () => TutorModel::query()->updateOrCreate(['id' => $tutor->id->value], [
                'laboratorio_id' => $tutor->laboratorioId, 'nome' => $tutor->nome(), 'celular' => $tutor->celular()->digits,
                'email' => $tutor->email()?->value, 'cpf' => $tutor->cpf()?->digits, 'cep' => $tutor->cep(),
                'email_autorizado_em' => $tutor->autorizacao()?->registradaEm,
                'email_autorizado_por' => $tutor->autorizacao()?->registradaPor,
            ]));
        } catch (UniqueConstraintViolationException $e) {
            $existente = $this->porCelular($tutor->celular());
            if ($existente !== null) {
                throw CelularJaCadastrado::para($existente->nome());
            }
            throw $e;
        }
    }

    public function porId(TutorId $id): ?Tutor
    {
        $m = TutorModel::query()->find($id->value);

        return $m === null ? null : $this->entity($m);
    }

    public function porCelular(Telefone $celular): ?Tutor
    {
        $m = TutorModel::query()->where('celular', $celular->digits)->first();

        return $m === null ? null : $this->entity($m);
    }

    public function buscar(string $termo, int $limite): array
    {
        $query = TutorModel::query();
        if (trim($termo) !== '') {
            $texto = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($termo)).'%';
            $digitos = preg_replace('/\D/', '', $termo) ?? '';
            $query->where(function ($q) use ($texto, $digitos): void {
                $q->whereRaw("lower(nome) like lower(?) escape '!'", [$texto])
                    ->orWhereIn('id', AnimalModel::query()->select('tutor_id')->whereRaw("lower(nome) like lower(?) escape '!'", [$texto]));
                if ($digitos !== '') {
                    $q->orWhere('celular', 'like', '%'.$digitos.'%')->orWhere('cpf', 'like', '%'.$digitos.'%');
                }
            });
        }

        return array_values($query->orderByDesc('created_at')->orderByDesc('id')->limit($limite)->get()->map(fn (TutorModel $m): Tutor => $this->entity($m))->all());
    }

    private function entity(TutorModel $m): Tutor
    {
        return Tutor::restaurar(new TutorId($m->id), $m->laboratorio_id, $m->nome, new Telefone($m->celular), $m->email === null ? null : new Email($m->email), $m->cpf === null ? null : new Cpf($m->cpf), $m->cep,
            $m->email_autorizado_em !== null && $m->email_autorizado_por !== null ? new AutorizacaoDeEmail($m->email_autorizado_em->toDateTimeImmutable(), $m->email_autorizado_por) : null);
    }
}
