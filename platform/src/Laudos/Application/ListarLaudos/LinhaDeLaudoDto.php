<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application\ListarLaudos;

final readonly class LinhaDeLaudoDto
{
    public function __construct(
        public string $id,
        public string $pedidoId,
        public string $numero,
        public string $protocolo,
        public string $paciente,
        public string $solicitante,
        public string $liberadoEm,
        public string $rt,
        public bool $retificado,
        public bool $emRetificacao,
        public bool $temValorCritico,
        public bool $emailOk,
        public bool $emailFalhou,
        public int $acessos,
        public ?string $ultimoAcesso,
    ) {}
}
