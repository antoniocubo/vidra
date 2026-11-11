<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain\Assinatura;

enum SituacaoDaAssinatura: string
{
    case EmTeste = 'em_teste';
    case Ativa = 'ativa';
    case Atrasada = 'atrasada';
    case SomenteConsulta = 'somente_consulta';

    public function rotulo(): string
    {
        return match ($this) {
            self::EmTeste => 'Em teste',
            self::Ativa => 'Ativa',
            self::Atrasada => 'Pagamento atrasado',
            self::SomenteConsulta => 'Só consulta',
        };
    }

    /** Em "só consulta" o laboratório vê e baixa tudo, mas não abre pedido novo. */
    public function abrePedido(): bool
    {
        return $this !== self::SomenteConsulta;
    }
}
