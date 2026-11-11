<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure\Asaas;

use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Vidra\Acesso\Domain\Assinatura\ClienteDeCobranca;
use Vidra\Acesso\Domain\Assinatura\GatewayDeCobranca;
use Vidra\Acesso\Domain\Assinatura\PagamentoDoGateway;
use Vidra\Acesso\Domain\Cobranca;
use Vidra\Acesso\Domain\Plano;

/** Para o ambiente local, sem ASAAS_API_KEY: só registra no log e devolve ids de mentira. */
final class GatewayDeCobrancaNulo implements GatewayDeCobranca
{
    public function garantirCliente(ClienteDeCobranca $laboratorio): string
    {
        Log::info('Cobrança (gateway nulo): cliente', ['laboratorio' => $laboratorio->laboratorioId]);

        return $laboratorio->clienteId ?? 'local_cli_'.Str::random(8);
    }

    public function criarAssinatura(string $clienteId, Plano $plano, Cobranca $cobranca, DateTimeImmutable $proximoVencimento): array
    {
        Log::info('Cobrança (gateway nulo): assinatura', ['cliente' => $clienteId, 'plano' => $plano->value, 'cobranca' => $cobranca->value, 'vencimento' => $proximoVencimento->format('Y-m-d')]);

        return ['id' => 'local_sub_'.Str::random(8), 'primeiroPagamento' => null];
    }

    public function cancelarAssinatura(string $assinaturaId): void
    {
        Log::info('Cobrança (gateway nulo): assinatura cancelada', ['assinatura' => $assinaturaId]);
    }

    public function cobrarAvulso(string $clienteId, int $valorCentavos, DateTimeImmutable $vencimento, string $descricao): PagamentoDoGateway
    {
        Log::info('Cobrança (gateway nulo): avulsa', ['cliente' => $clienteId, 'valor' => $valorCentavos, 'descricao' => $descricao]);

        return new PagamentoDoGateway('local_pay_'.Str::random(8), null, $valorCentavos, $vencimento->format('Y-m-d'));
    }
}
