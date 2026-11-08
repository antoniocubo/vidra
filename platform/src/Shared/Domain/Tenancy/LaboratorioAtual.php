<?php

declare(strict_types=1);

namespace Vidra\Shared\Domain\Tenancy;

/**
 * O laboratório em que a requisição (ou o job) está trabalhando.
 * Todo dado de laboratório é lido e gravado só dentro dele.
 */
interface LaboratorioAtual
{
    /** @throws LaboratorioNaoDefinido quando nada definiu o laboratório antes da consulta */
    public function id(): string;

    public function definido(): bool;
}
