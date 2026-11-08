<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Portas;

/** Porta do núcleo para o paciente; cada linha de produto implementa a sua. */
interface Pacientes
{
    public function resumo(string $pacienteId): ?PacienteDoPedido;

    /**
     * @param  list<string>  $pacienteIds
     * @return array<string, PacienteDoPedido> indexados pelo id; ids desconhecidos ficam de fora
     */
    public function resumos(array $pacienteIds): array;

    /**
     * Busca por telefone, CPF, nome do responsável ou do paciente.
     *
     * @return list<PacienteDoPedido>
     */
    public function buscar(string $termo, int $limite): array;
}
