<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Portas;

interface CatalogoDeExames
{
    /** Só exame ativo do laboratório atual; inativo ou de outro laboratório volta null. */
    public function ativo(string $exameId): ?ExameDoPedido;

    /** @return list<ExameDoPedido> ativos, por nome */
    public function buscar(string $termo, int $limite): array;
}
