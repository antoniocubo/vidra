<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain\Assinatura;

use InvalidArgumentException;
use Vidra\Acesso\Domain\Cobranca;
use Vidra\Acesso\Domain\Plano;

/**
 * Pedidos do mês contra o limite do plano. Cobra-se por pedido (atendimento),
 * não por exame; o limite é mensal também no plano anual. Pedido cancelado
 * inteiro não conta (quem conta é a infraestrutura, com essa regra).
 */
final readonly class ConsumoDoMes
{
    public const int EXCEDENTE_POR_PEDIDO_CENTAVOS = 25;

    public function __construct(public Plano $plano, public Cobranca $cobranca, public int $pedidos)
    {
        if ($pedidos < 0) {
            throw new InvalidArgumentException('Contagem de pedidos negativa.');
        }
    }

    public function limite(): int
    {
        return $this->plano->pedidos();
    }

    public function excedente(): int
    {
        return max(0, $this->pedidos - $this->limite());
    }

    public function valorExcedenteCentavos(): int
    {
        return $this->excedente() * self::EXCEDENTE_POR_PEDIDO_CENTAVOS;
    }

    /** O que entra na cobrança deste mês: a mensalidade (no mensal) mais o excedente. */
    public function valorDoMesCentavos(): int
    {
        $mensalidade = $this->cobranca === Cobranca::Mensal ? $this->plano->precoEmCentavos(Cobranca::Mensal) : 0;

        return $mensalidade + $this->valorExcedenteCentavos();
    }

    /** 0 a 100+, para a barra de uso. */
    public function percentualUsado(): int
    {
        return intdiv($this->pedidos * 100, $this->limite());
    }
}
