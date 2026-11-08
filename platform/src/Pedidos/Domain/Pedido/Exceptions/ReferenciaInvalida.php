<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Pedido\Exceptions;

use Vidra\Shared\Domain\DomainException;

/** Algo que o pedido cita (paciente, solicitante, clínica, exame) não existe neste laboratório. */
final class ReferenciaInvalida extends DomainException
{
    private function __construct(string $mensagem, public readonly string $campo)
    {
        parent::__construct($mensagem);
    }

    public static function paciente(): self
    {
        return new self('Escolha o paciente do pedido.', 'paciente_id');
    }

    public static function solicitante(): self
    {
        return new self('Escolha quem pede os exames.', 'solicitante_id');
    }

    public static function clinica(): self
    {
        return new self('Esta clínica não está disponível. Escolha outra.', 'clinica_id');
    }

    public static function exame(): self
    {
        return new self('Um dos exames não está mais disponível no catálogo. Remova-o e tente de novo.', 'exame_ids');
    }
}
