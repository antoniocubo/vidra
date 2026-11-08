<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Amostra\Exceptions;

use DateTimeImmutable;
use DateTimeZone;
use Vidra\Shared\Domain\DomainException;

final class TriagemInvalida extends DomainException
{
    public static function jaRecebida(string $codigo, DateTimeImmutable $recebidaEm): self
    {
        $hora = $recebidaEm->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('H:i');

        return new self("A amostra {$codigo} já foi recebida às {$hora}.");
    }

    public static function aindaNaoRecebida(string $codigo): self
    {
        return new self("A amostra {$codigo} ainda não foi recebida. Bipe a etiqueta primeiro.");
    }

    public static function jaTriada(string $codigo): self
    {
        return new self("A amostra {$codigo} já passou pela triagem.");
    }

    public static function soRejeitadaPedeRecoleta(string $codigo): self
    {
        return new self("Só uma amostra rejeitada pede recoleta, e a {$codigo} não foi rejeitada.");
    }

    public static function recoletaJaPedida(string $codigo, string $novoCodigo): self
    {
        return new self("A recoleta da amostra {$codigo} já foi pedida: a nova amostra é a {$novoCodigo}.");
    }

    public static function motivoSemTexto(): self
    {
        return new self('Descreva o motivo da rejeição.');
    }

    public static function ressalvaSemTexto(): self
    {
        return new self('Escreva a ressalva que vai para o laudo.');
    }
}
