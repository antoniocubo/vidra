<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\PrepararNovoPedido;

use Vidra\Pedidos\Domain\Portas\ClinicaPagadora;
use Vidra\Pedidos\Domain\Portas\PacienteDoPedido;
use Vidra\Pedidos\Domain\Portas\TextosDaLinha;

final readonly class NovoPedidoDto
{
    /**
     * @param  list<SolicitanteParaEscolhaDto>  $solicitantes
     * @param  list<ClinicaPagadora>  $clinicasAtivas
     */
    public function __construct(public array $solicitantes, public array $clinicasAtivas, public ?PacienteDoPedido $paciente, public TextosDaLinha $textos) {}
}
