<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\PrepararNovoPedido;

use Vidra\Pedidos\Domain\Portas\Pacientes;
use Vidra\Pedidos\Domain\Portas\Pagadores;
use Vidra\Pedidos\Domain\Portas\SolicitanteDoPedido;
use Vidra\Pedidos\Domain\Portas\Solicitantes;
use Vidra\Pedidos\Domain\Portas\Vocabulario;

final readonly class PrepararNovoPedido
{
    public function __construct(private Solicitantes $solicitantes, private Pagadores $pagadores, private Pacientes $pacientes, private Vocabulario $vocabulario) {}

    public function __invoke(?string $pacienteId = null): NovoPedidoDto
    {
        $todas = $this->pagadores->todasAtivas();
        $solicitantes = array_map(function (SolicitanteDoPedido $s) use ($todas): SolicitanteParaEscolhaDto {
            $dele = $this->pagadores->clinicasDoSolicitante($s->id);

            // Sem clínica própria, qualquer clínica ativa pode receber a fatura.
            return new SolicitanteParaEscolhaDto($s->id, $s->nome, $s->registro, $s->pagadorPadrao, $dele === [] ? $todas : $dele);
        }, $this->solicitantes->todos());

        return new NovoPedidoDto($solicitantes, $todas, $pacienteId === null ? null : $this->pacientes->resumo($pacienteId), $this->vocabulario->textos());
    }
}
