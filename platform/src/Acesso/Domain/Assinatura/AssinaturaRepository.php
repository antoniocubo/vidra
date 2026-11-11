<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain\Assinatura;

use DateTimeImmutable;

interface AssinaturaRepository
{
    public function doLaboratorio(string $laboratorioId): Assinatura;

    /** Grava plano, cobrança e `pago_ate`. */
    public function save(Assinatura $assinatura): void;

    public function cliente(string $laboratorioId): ClienteDeCobranca;

    public function vincularCliente(string $laboratorioId, string $clienteId): void;

    public function vincularAssinatura(string $laboratorioId, ?string $assinaturaId): void;

    public function laboratorioDoCliente(string $clienteId): ?string;

    /** @return list<string> */
    public function emailsDosAdministradores(string $laboratorioId): array;

    /** AAAA-MM-DD (São Paulo) do último aviso. */
    public function avisadaEm(string $laboratorioId): ?string;

    public function marcarAvisada(string $laboratorioId, DateTimeImmutable $quando): void;
}
