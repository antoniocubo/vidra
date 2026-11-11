<?php

declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;
use DateTimeZone;
use Vidra\Acesso\Domain\Assinatura\Assinatura;
use Vidra\Acesso\Domain\Assinatura\AssinaturaRepository;
use Vidra\Acesso\Domain\Assinatura\ClienteDeCobranca;

final class InMemoryAssinaturaRepository implements AssinaturaRepository
{
    /** @var array<string, Assinatura> */
    public array $assinaturas = [];

    /** @var array<string, array{cliente?: ?string, assinatura?: ?string, avisada?: ?string}> */
    public array $vinculos = [];

    /** @var array<string, list<string>> */
    public array $emails = [];

    public function doLaboratorio(string $laboratorioId): Assinatura
    {
        return $this->assinaturas[$laboratorioId];
    }

    public function save(Assinatura $assinatura): void
    {
        $this->assinaturas[$assinatura->laboratorioId] = $assinatura;
    }

    public function cliente(string $laboratorioId): ClienteDeCobranca
    {
        $v = $this->vinculos[$laboratorioId] ?? [];

        return new ClienteDeCobranca($laboratorioId, 'Laboratório Alfa', '11222333000181', 'admin@alfa.test', $v['cliente'] ?? null, $v['assinatura'] ?? null);
    }

    public function vincularCliente(string $laboratorioId, string $clienteId): void
    {
        $this->vinculos[$laboratorioId]['cliente'] = $clienteId;
    }

    public function vincularAssinatura(string $laboratorioId, ?string $assinaturaId): void
    {
        $this->vinculos[$laboratorioId]['assinatura'] = $assinaturaId;
    }

    public function laboratorioDoCliente(string $clienteId): ?string
    {
        foreach ($this->vinculos as $laboratorioId => $v) {
            if (($v['cliente'] ?? null) === $clienteId) {
                return $laboratorioId;
            }
        }

        return null;
    }

    public function emailsDosAdministradores(string $laboratorioId): array
    {
        return $this->emails[$laboratorioId] ?? [];
    }

    public function avisadaEm(string $laboratorioId): ?string
    {
        return $this->vinculos[$laboratorioId]['avisada'] ?? null;
    }

    public function marcarAvisada(string $laboratorioId, DateTimeImmutable $quando): void
    {
        $this->vinculos[$laboratorioId]['avisada'] = $quando->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('Y-m-d');
    }
}
