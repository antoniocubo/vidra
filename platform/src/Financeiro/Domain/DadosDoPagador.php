<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Domain;

/** Quem paga, mais os dias combinados para fechar e vencer a fatura. */
final readonly class DadosDoPagador
{
    /** O veterinário solicitante fecha no dia 1 e vence no dia 10. */
    private const int FECHAMENTO_DO_SOLICITANTE = 1;

    private const int VENCIMENTO_DO_SOLICITANTE = 10;

    public function __construct(public PagadorDaFatura $pagador, public int $diaDeFechamento, public int $diaDeVencimento) {}

    public static function doSolicitante(PagadorDaFatura $pagador): self
    {
        return new self($pagador, self::FECHAMENTO_DO_SOLICITANTE, self::VENCIMENTO_DO_SOLICITANTE);
    }
}
