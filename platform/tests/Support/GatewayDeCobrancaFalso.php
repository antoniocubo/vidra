<?php

declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;
use Vidra\Acesso\Domain\Assinatura\ClienteDeCobranca;
use Vidra\Acesso\Domain\Assinatura\GatewayDeCobranca;
use Vidra\Acesso\Domain\Assinatura\PagamentoDoGateway;
use Vidra\Acesso\Domain\Cobranca;
use Vidra\Acesso\Domain\Plano;

final class GatewayDeCobrancaFalso implements GatewayDeCobranca
{
    /** @var list<string> */
    public array $chamadas = [];

    /** @var list<array{int, string}> */
    public array $avulsas = [];

    public function garantirCliente(ClienteDeCobranca $laboratorio): string
    {
        $this->chamadas[] = 'cliente';

        return $laboratorio->clienteId ?? 'cus_1';
    }

    public function criarAssinatura(string $clienteId, Plano $plano, Cobranca $cobranca, DateTimeImmutable $proximoVencimento): array
    {
        $this->chamadas[] = 'assinatura:'.$plano->value.':'.$cobranca->value.':'.$proximoVencimento->format('Y-m-d');

        return ['id' => 'sub_'.count($this->chamadas), 'primeiroPagamento' => new PagamentoDoGateway('pay_'.count($this->chamadas), 'https://asaas.test/i/1', $plano->precoEmCentavos($cobranca), $proximoVencimento->format('Y-m-d'))];
    }

    public function cancelarAssinatura(string $assinaturaId): void
    {
        $this->chamadas[] = 'cancelar:'.$assinaturaId;
    }

    public function cobrarAvulso(string $clienteId, int $valorCentavos, DateTimeImmutable $vencimento, string $descricao): PagamentoDoGateway
    {
        $this->avulsas[] = [$valorCentavos, $descricao];

        return new PagamentoDoGateway('pay_av'.count($this->avulsas), 'https://asaas.test/i/av', $valorCentavos, $vencimento->format('Y-m-d'));
    }
}
