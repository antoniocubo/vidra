<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\ParaBancada;

use DateTimeImmutable;

final readonly class ItemParaBancada
{
    public function __construct(public string $id, public string $pedidoId, public string $laboratorioId, public string $exameId, public string $exame, public string $setor, public string $situacao, public string $pacienteId, public string $paciente, public string $descricao, public string $especieCurta, public ?string $perfil, public string $protocolo, public bool $urgente, public string $amostra, public ?DateTimeImmutable $chegada, public string $solicitante, public string $telefoneCritico) {}
}
