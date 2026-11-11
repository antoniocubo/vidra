<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\PedidosDoVeterinario;

use DateTimeZone;
use Vidra\Laudos\Application\LaudoDoPedido;
use Vidra\Laudos\Application\MontarLaudo\MontarLaudo;
use Vidra\Pedidos\Application\DetalharPedido\DetalharPedido;
use Vidra\Pedidos\Application\Texto;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Veterinaria\Domain\Acesso\LaboratorioDoVeterinario;
use Vidra\Veterinaria\Domain\Acesso\PedidosVisiveis;
use Vidra\Veterinaria\Domain\Relogio;

final readonly class PedidosDoVeterinario
{
    public function __construct(private LaboratorioAtual $laboratorio, private PedidosVisiveis $visiveis, private DetalharPedido $detalhar, private LaudoDoPedido $laudo, private MontarLaudo $montar, private Relogio $relogio) {}

    public function __invoke(LaboratorioDoVeterinario $acesso, PedidosDoVeterinarioInput $input = new PedidosDoVeterinarioInput): PaginaDePedidosDto
    {
        $this->laboratorio->id();
        $zona = new DateTimeZone('America/Sao_Paulo');
        $hoje = $this->relogio->agora()->setTimezone($zona);
        $resumo = ['andamento' => 0, 'prontosHoje' => 0, 'criticosHoje' => 0, 'mes' => 0];
        $linhas = [];
        // shortcut: lista e busca olham os 300 pedidos mais recentes; o download vale para todos. Paginar no banco quando um vínculo passar disso.
        foreach (array_slice($this->visiveis->doLaboratorio($acesso), 0, 300, true) as $id => $criadoEm) {
            $pedido = ($this->detalhar)($id);
            $laudo = ($this->laudo)($id);
            $critico = $laudo !== null && $laudo->criticos > 0;
            if ($laudo === null && $pedido->situacao !== 'cancelado') {
                $resumo['andamento']++;
            }
            if ($laudo?->liberadoEm->setTimezone($zona)->format('Y-m-d') === $hoje->format('Y-m-d')) {
                $resumo['prontosHoje']++;
                $resumo['criticosHoje'] += (int) $critico;
            }
            if ($criadoEm->setTimezone($zona)->format('Y-m') === $hoje->format('Y-m')) {
                $resumo['mes']++;
            }
            if (Texto::casa($input->busca, [$pedido->paciente['nome'], $pedido->paciente['responsavelNome']])) {
                $linhas[] = [$id, $pedido, $laudo, $critico];
            }
        }
        $total = count($linhas);
        $paginas = max(1, (int) ceil($total / 20));
        $pagina = min(max(1, $input->pagina), $paginas);
        // O laudo montado (PDF pronto, link do WhatsApp) só para as linhas da página.
        $daPagina = array_map(function (array $l): PedidoDoVeterinarioDto {
            [$id, $pedido, $laudo, $critico] = $l;
            $conteudo = $laudo === null ? null : ($this->montar)($id);

            return new PedidoDoVeterinarioDto($id, $pedido->paciente['nome'], $pedido->paciente['responsavelNome'], array_column(array_filter($pedido->itens, fn (array $i): bool => $i['situacao'] !== 'cancelado'), 'nome'), $laudo === null ? $pedido->situacao : 'liberado', $laudo === null ? $pedido->situacaoRotulo : 'Pronto', $critico, $conteudo === null ? false : $conteudo->pdfPronto, $conteudo === null ? null : ($conteudo->whatsapp ?? 'https://wa.me/?text='.rawurlencode($conteudo->textoWhatsapp)));
        }, array_slice($linhas, ($pagina - 1) * 20, 20));

        return new PaginaDePedidosDto($daPagina, $resumo, $total, $pagina, $paginas);
    }
}
