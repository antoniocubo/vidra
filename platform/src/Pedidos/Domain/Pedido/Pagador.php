<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Pedido;

use Vidra\Pedidos\Domain\Pedido\Exceptions\PagadorInvalido;

/** Quem paga o pedido: a clínica (fatura mensal), o solicitante (fatura mensal) ou o responsável pelo paciente (balcão). */
final readonly class Pagador
{
    public function __construct(public TipoDePagador $tipo, public ?string $clinicaId = null)
    {
        if ($tipo === TipoDePagador::Clinica && $clinicaId === null) {
            throw PagadorInvalido::clinicaSemId();
        }

        if ($tipo !== TipoDePagador::Clinica && $clinicaId !== null) {
            throw PagadorInvalido::clinicaSoParaClinica();
        }
    }

    public static function clinica(string $clinicaId): self
    {
        return new self(TipoDePagador::Clinica, $clinicaId);
    }

    public static function solicitante(): self
    {
        return new self(TipoDePagador::Solicitante);
    }

    public static function responsavel(): self
    {
        return new self(TipoDePagador::Responsavel);
    }

    public function ehClinica(): bool
    {
        return $this->tipo === TipoDePagador::Clinica;
    }
}
