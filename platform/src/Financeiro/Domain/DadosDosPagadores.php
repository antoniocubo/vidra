<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Domain;

/** Porta para a linha de produto: quem paga a fatura e o nome do paciente de cada pedido. */
interface DadosDosPagadores
{
    public function clinica(string $id): ?DadosDoPagador;

    public function solicitante(string $id): ?DadosDoPagador;

    /**
     * @param  list<string>  $ids
     * @return array<string, string> nome por id do paciente
     */
    public function pacientes(array $ids): array;
}
