<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure\Persistence;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Vidra\Acesso\Domain\Administrador;
use Vidra\Acesso\Domain\CnpjJaCadastrado;
use Vidra\Acesso\Domain\EmailJaCadastrado;
use Vidra\Acesso\Domain\Laboratorio;
use Vidra\Acesso\Domain\LaboratorioId;
use Vidra\Acesso\Domain\LaboratorioRepository;
use Vidra\Shared\Domain\ValueObjects\Cnpj;
use Vidra\Shared\Domain\ValueObjects\Email;

final class EloquentLaboratorioRepository implements LaboratorioRepository
{
    public function nextIdentity(): LaboratorioId
    {
        return new LaboratorioId((string) Str::uuid7());
    }

    public function existeCnpj(Cnpj $cnpj): bool
    {
        return LaboratorioModel::query()->where('cnpj', $cnpj->value)->exists();
    }

    public function existeEmail(Email $email): bool
    {
        return User::query()->whereRaw('lower(email) = ?', [$email->value])->exists();
    }

    public function save(Laboratorio $laboratorio, Administrador $administrador): void
    {
        try {
            DB::transaction(function () use ($laboratorio, $administrador): void {
                LaboratorioModel::query()->create([
                    'id' => $laboratorio->id->value, 'nome' => $laboratorio->nome, 'cnpj' => $laboratorio->cnpj->value,
                    'telefone' => $laboratorio->telefone->digits, 'cep' => $laboratorio->endereco->cep,
                    'cidade' => $laboratorio->endereco->cidade, 'uf' => $laboratorio->endereco->uf->value, 'endereco' => $laboratorio->endereco->endereco,
                    'linha' => $laboratorio->linha->value, 'plano' => $laboratorio->plano->value, 'cobranca' => $laboratorio->cobranca->value,
                    'teste_termina_em' => $laboratorio->testeTerminaEm,
                    'rt_nome' => $laboratorio->rt->nome, 'rt_conselho' => $laboratorio->rt->registro->conselho,
                    'rt_numero' => $laboratorio->rt->registro->numero, 'rt_uf' => $laboratorio->rt->registro->uf->value,
                    'rt_titulo' => $laboratorio->rt->titulo, 'rt_email' => $laboratorio->rt->email?->value,
                ]);
                $user = new User;
                $user->name = $administrador->nome;
                $user->email = $administrador->email->value;
                $user->password = $administrador->senha;
                $user->setAttribute('celular', $administrador->celular->digits);
                $user->save();
                DB::table('laboratorio_usuario')->insert(['laboratorio_id' => $laboratorio->id->value, 'user_id' => $user->id, 'papel' => 'admin', 'e_rt' => $administrador->eRt]);
            });
        } catch (UniqueConstraintViolationException $exception) {
            // A restrição também protege cadastros concorrentes, após o rollback.
            if ($this->existeCnpj($laboratorio->cnpj)) {
                throw CnpjJaCadastrado::para($laboratorio->cnpj);
            }
            if ($this->existeEmail($administrador->email)) {
                throw EmailJaCadastrado::para($administrador->email);
            }
            throw $exception;
        }
    }
}
