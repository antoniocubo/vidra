<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\ParaLaudo;

use DateTimeImmutable;

final readonly class PedidoParaLaudo
{
    /**
     * @param  array{paciente:string,responsavel:string,solicitante:string}  $textos
     * @param  list<array{id:string,exameId:string,nome:string,material:string}>  $itens
     * @param  list<array{recebidaEm:?DateTimeImmutable,ressalva:?string}>  $amostras
     */
    public function __construct(public string $protocolo, public DateTimeImmutable $criadoEm, public string $solicitanteId,
        public string $paciente, public string $descricao, public string $responsavel, public ?string $emailResponsavel,
        public bool $autorizaEmail, public string $telefone, public string $solicitante, public string $registro,
        public string $emailSolicitante, public array $textos, public array $itens, public array $amostras) {}
}
