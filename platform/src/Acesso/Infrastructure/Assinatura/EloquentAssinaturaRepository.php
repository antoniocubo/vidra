<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure\Assinatura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use stdClass;
use Vidra\Acesso\Domain\Assinatura\Assinatura;
use Vidra\Acesso\Domain\Assinatura\AssinaturaRepository;
use Vidra\Acesso\Domain\Assinatura\ClienteDeCobranca;
use Vidra\Acesso\Domain\Cobranca;
use Vidra\Acesso\Domain\Plano;

/** `laboratorios` e `users` são globais: tudo pela conexão de sistema, sempre filtrando o laboratório. */
final class EloquentAssinaturaRepository implements AssinaturaRepository
{
    public function doLaboratorio(string $laboratorioId): Assinatura
    {
        $linha = $this->linha($laboratorioId);

        return Assinatura::restaurar(
            $laboratorioId, Plano::from((string) $linha->plano), Cobranca::from((string) $linha->cobranca),
            $this->data($linha->teste_termina_em), $linha->pago_ate === null ? null : $this->data($linha->pago_ate),
        );
    }

    public function save(Assinatura $assinatura): void
    {
        $this->conexao()->table('laboratorios')->where('id', $assinatura->laboratorioId)->update([
            'plano' => $assinatura->plano()->value, 'cobranca' => $assinatura->cobranca()->value,
            'pago_ate' => $assinatura->pagoAte()?->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'updated_at' => now(),
        ]);
    }

    public function cliente(string $laboratorioId): ClienteDeCobranca
    {
        $linha = $this->linha($laboratorioId);

        return new ClienteDeCobranca(
            $laboratorioId, (string) $linha->nome, (string) $linha->cnpj,
            ($linha->rt_email ?? null) ?: ($this->emailsDosAdministradores($laboratorioId)[0] ?? null),
            $linha->asaas_cliente_id, $linha->asaas_assinatura_id,
        );
    }

    public function vincularCliente(string $laboratorioId, string $clienteId): void
    {
        $this->conexao()->table('laboratorios')->where('id', $laboratorioId)->update(['asaas_cliente_id' => $clienteId]);
    }

    public function vincularAssinatura(string $laboratorioId, ?string $assinaturaId): void
    {
        $this->conexao()->table('laboratorios')->where('id', $laboratorioId)->update(['asaas_assinatura_id' => $assinaturaId]);
    }

    public function laboratorioDoCliente(string $clienteId): ?string
    {
        $id = $this->conexao()->table('laboratorios')->where('asaas_cliente_id', $clienteId)->value('id');

        return $id === null ? null : (string) $id;
    }

    public function emailsDosAdministradores(string $laboratorioId): array
    {
        return array_values($this->conexao()->table('laboratorio_usuario')->join('users', 'users.id', '=', 'laboratorio_usuario.user_id')
            ->where('laboratorio_usuario.laboratorio_id', $laboratorioId)->where('laboratorio_usuario.papel', 'admin')
            ->orderBy('users.id')->pluck('users.email')->map(static fn (mixed $email): string => (string) $email)->all());
    }

    public function avisadaEm(string $laboratorioId): ?string
    {
        $dia = $this->conexao()->table('laboratorios')->where('id', $laboratorioId)->value('assinatura_avisada_em');

        return $dia === null ? null : substr((string) $dia, 0, 10);
    }

    public function marcarAvisada(string $laboratorioId, DateTimeImmutable $quando): void
    {
        $this->conexao()->table('laboratorios')->where('id', $laboratorioId)->update([
            'assinatura_avisada_em' => $quando->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('Y-m-d'),
        ]);
    }

    private function conexao(): Connection
    {
        return DB::connection(config()->string('vidra.conexao_sistema'));
    }

    private function linha(string $laboratorioId): stdClass
    {
        return $this->conexao()->table('laboratorios')->where('id', $laboratorioId)->first() ?? throw new RuntimeException("Laboratório {$laboratorioId} não existe.");
    }

    private function data(mixed $valor): DateTimeImmutable
    {
        return new DateTimeImmutable((string) $valor, new DateTimeZone('UTC'));
    }
}
