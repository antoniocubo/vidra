<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

use DateTimeImmutable;

final readonly class ResultadoParaConferencia
{
    public function __construct(public string $id, public string $itemId, public string $exame, public string $nome, public string $valor, public ?string $unidade, public string $classificacao, public string $referencia, public string $digitador, public DateTimeImmutable $digitadoEm, public ?string $anterior, public ?DateTimeImmutable $anteriorEm, public bool $delta, public ?int $percentual, public ?AvisoDeValorCritico $aviso) {}

    public function critico(): bool
    {
        return in_array($this->classificacao, ['critico_alto', 'critico_baixo'], true);
    }

    public function avisado(): bool
    {
        return $this->aviso !== null && $this->aviso->valorAvisado === $this->valor && $this->aviso->resultadoDigitadoEm->format('U.u') === $this->digitadoEm->format('U.u');
    }
}
