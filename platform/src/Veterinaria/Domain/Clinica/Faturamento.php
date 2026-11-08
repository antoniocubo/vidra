<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Clinica;

use Vidra\Shared\Domain\ValueObjects\Email;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;

/**
 * Como a fatura mensal da clínica é montada e cobrada. O desconto é a "tabela de
 * preço": 0 usa a tabela base, 10 é a parceira com 10% a menos.
 */
final readonly class Faturamento
{
    /** Valor especial do dia de fechamento: o último dia do mês, seja ele 28, 29, 30 ou 31. */
    public const int ULTIMO_DIA = 31;

    private const int MAIOR_DIA_FIXO = 28;

    public function __construct(
        public Email $email,
        public int $desconto,
        public int $diaDeFechamento,
        public int $diaDeVencimento,
        public FormaDePagamento $forma,
    ) {
        if ($desconto < 0 || $desconto > 100) {
            throw InvalidValue::for('desconto', 'use um valor de 0 a 100');
        }

        if ($diaDeFechamento !== self::ULTIMO_DIA && ($diaDeFechamento < 1 || $diaDeFechamento > self::MAIOR_DIA_FIXO)) {
            throw InvalidValue::for('dia de fechamento', 'use um dia de 1 a 28 ou o último dia do mês');
        }

        if ($diaDeVencimento < 1 || $diaDeVencimento > self::MAIOR_DIA_FIXO) {
            throw InvalidValue::for('dia de vencimento', 'use um dia de 1 a 28');
        }
    }

    public function fechaNoUltimoDia(): bool
    {
        return $this->diaDeFechamento === self::ULTIMO_DIA;
    }
}
