<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

use DateTimeImmutable;

interface ConsultaDeLaudos
{
    /**
     * @return list<array{id: string, pedidoId: string, numero: string, protocolo: string, paciente: string, solicitante: string, liberadoEm: string, rt: string, retificado: bool, emRetificacao: bool, temValorCritico: bool, emailOk: bool, emailFalhou: bool, acessos: int, ultimoAcesso: ?string}> Até 26 linhas, incluindo a sentinela da próxima página.
     */
    public function pagina(string $laboratorioId, string $busca, ?DateTimeImmutable $de, ?DateTimeImmutable $ate, string $entrega, int $pagina): array;
}
