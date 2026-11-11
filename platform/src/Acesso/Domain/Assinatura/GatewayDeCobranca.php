<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain\Assinatura;

use DateTimeImmutable;
use Vidra\Acesso\Domain\Cobranca;
use Vidra\Acesso\Domain\Plano;

interface GatewayDeCobranca
{
    /** Devolve o cliente já criado ou cria um; o chamador guarda o id. */
    public function garantirCliente(ClienteDeCobranca $laboratorio): string;

    /** @return array{id: string, primeiroPagamento: ?PagamentoDoGateway} */
    public function criarAssinatura(string $clienteId, Plano $plano, Cobranca $cobranca, DateTimeImmutable $proximoVencimento): array;

    public function cancelarAssinatura(string $assinaturaId): void;

    public function cobrarAvulso(string $clienteId, int $valorCentavos, DateTimeImmutable $vencimento, string $descricao): PagamentoDoGateway;
}
