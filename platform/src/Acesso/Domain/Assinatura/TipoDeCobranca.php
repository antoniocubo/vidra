<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain\Assinatura;

enum TipoDeCobranca: string
{
    case Mensalidade = 'mensalidade';
    case Anuidade = 'anuidade';
    case Excedente = 'excedente';

    public function rotulo(): string
    {
        return match ($this) {
            self::Mensalidade => 'Mensalidade',
            self::Anuidade => 'Anuidade',
            self::Excedente => 'Excedente de pedidos',
        };
    }

    /** Só mensalidade e anuidade estendem a assinatura; o excedente é cobrado à parte. */
    public function estendeAssinatura(): bool
    {
        return $this !== self::Excedente;
    }
}
