<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure\Asaas;

use DateTimeImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Vidra\Acesso\Domain\Assinatura\ClienteDeCobranca;
use Vidra\Acesso\Domain\Assinatura\FalhaNaCobranca;
use Vidra\Acesso\Domain\Assinatura\GatewayDeCobranca;
use Vidra\Acesso\Domain\Assinatura\PagamentoDoGateway;
use Vidra\Acesso\Domain\Cobranca;
use Vidra\Acesso\Domain\Plano;

/**
 * Asaas v3. `UNDEFINED`: o laboratório escolhe boleto, Pix ou cartão na fatura do Asaas.
 * Só as leituras têm retry: repetir um POST que expirou poderia criar cliente ou cobrança em dobro.
 */
final readonly class AsaasGateway implements GatewayDeCobranca
{
    public function __construct(private string $chave, private string $ambiente) {}

    public function garantirCliente(ClienteDeCobranca $laboratorio): string
    {
        if ($laboratorio->clienteId !== null) {
            return $laboratorio->clienteId;
        }

        return $this->id($this->enviar('post', '/customers', [
            'name' => $laboratorio->nome, 'cpfCnpj' => $laboratorio->cnpj, 'email' => $laboratorio->email,
            'externalReference' => $laboratorio->laboratorioId,
        ]));
    }

    public function criarAssinatura(string $clienteId, Plano $plano, Cobranca $cobranca, DateTimeImmutable $proximoVencimento): array
    {
        $assinatura = $this->enviar('post', '/subscriptions', [
            'customer' => $clienteId, 'billingType' => 'UNDEFINED', 'value' => $plano->precoEmCentavos($cobranca) / 100,
            'nextDueDate' => $proximoVencimento->format('Y-m-d'), 'cycle' => $cobranca === Cobranca::Anual ? 'YEARLY' : 'MONTHLY',
            'description' => sprintf('Vidra %s (%s)', $plano->nome(), $cobranca->value),
        ]);
        $id = $this->id($assinatura);
        $primeiro = $this->enviar('get', "/subscriptions/{$id}/payments")['data'][0] ?? null;

        return ['id' => $id, 'primeiroPagamento' => is_array($primeiro) ? $this->pagamento($primeiro) : null];
    }

    public function cancelarAssinatura(string $assinaturaId): void
    {
        $this->enviar('delete', "/subscriptions/{$assinaturaId}");
    }

    public function cobrarAvulso(string $clienteId, int $valorCentavos, DateTimeImmutable $vencimento, string $descricao): PagamentoDoGateway
    {
        return $this->pagamento($this->enviar('post', '/payments', [
            'customer' => $clienteId, 'billingType' => 'UNDEFINED', 'value' => $valorCentavos / 100,
            'dueDate' => $vencimento->format('Y-m-d'), 'description' => $descricao,
        ]));
    }

    /**
     * @param  array<string, mixed>  $corpo
     * @return array<string, mixed>
     */
    private function enviar(string $metodo, string $caminho, array $corpo = []): array
    {
        try {
            $resposta = $this->cliente($metodo === 'get')->{$metodo}($caminho, $corpo);
        } catch (ConnectionException) {
            throw FalhaNaCobranca::indisponivel();
        }
        $this->conferir($resposta);

        return (array) $resposta->json();
    }

    private function cliente(bool $leitura): PendingRequest
    {
        $url = $this->ambiente === 'producao' ? 'https://api.asaas.com/v3' : 'https://sandbox.asaas.com/api/v3';
        $cliente = Http::baseUrl($url)->withHeaders(['access_token' => $this->chave])->acceptJson()->timeout(10)->connectTimeout(5);

        return $leitura ? $cliente->retry(2, 200, throw: false) : $cliente;
    }

    private function conferir(Response $resposta): void
    {
        if ($resposta->serverError()) {
            throw FalhaNaCobranca::indisponivel();
        }
        if ($resposta->failed()) {
            $erro = $resposta->json('errors.0.description');
            throw FalhaNaCobranca::recusada(is_string($erro) ? $erro : 'verifique os dados do laboratório (CNPJ e e-mail).');
        }
    }

    /** @param array<string, mixed> $dados */
    private function id(array $dados): string
    {
        return is_string($dados['id'] ?? null) ? $dados['id'] : throw FalhaNaCobranca::indisponivel();
    }

    /** @param array<string, mixed> $p */
    private function pagamento(array $p): PagamentoDoGateway
    {
        return new PagamentoDoGateway(
            $this->id($p), is_string($p['invoiceUrl'] ?? null) ? $p['invoiceUrl'] : null,
            (int) round(((float) ($p['value'] ?? 0)) * 100), is_string($p['dueDate'] ?? null) ? $p['dueDate'] : date('Y-m-d'),
        );
    }
}
