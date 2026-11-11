<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain\Assinatura;

use DateTimeImmutable;

/** Uma cobrança que o Vidra faz ao laboratório (espelho do pagamento no gateway). */
final readonly class CobrancaDoVidra
{
    public function __construct(
        public string $laboratorioId,
        public string $pagamentoId,
        public TipoDeCobranca $tipo,
        public ?string $competencia,
        public int $valorCentavos,
        public SituacaoDaCobranca $situacao,
        /** AAAA-MM-DD */
        public string $vencimento,
        public ?string $link,
        public ?DateTimeImmutable $pagaEm = null,
    ) {}
}
