<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application\Assinatura;

use DateTimeZone;
use Vidra\Acesso\Domain\Assinatura\AssinaturaRepository;
use Vidra\Acesso\Domain\Assinatura\CobrancaDoVidra;
use Vidra\Acesso\Domain\Assinatura\CobrancasDoVidra;
use Vidra\Acesso\Domain\Assinatura\PedidosDoMes;
use Vidra\Acesso\Domain\Relogio;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class ResumoDaAssinatura
{
    public function __construct(
        private LaboratorioAtual $laboratorio,
        private AssinaturaRepository $assinaturas,
        private CobrancasDoVidra $cobrancas,
        private PedidosDoMes $pedidos,
        private Relogio $relogio,
    ) {}

    /**
     * @return array{
     *   plano: string, cobranca: string, situacao: string, situacaoRotulo: string, diasRestantes: int, validaAte: string, pagoAte: ?string,
     *   consumo: array{mes: string, pedidos: int, limite: int, percentual: int, excedente: int, valorExcedente: int},
     *   cobrancas: list<array{id: string, tipo: string, tipoRotulo: string, competencia: ?string, valor: int, situacao: string, situacaoRotulo: string, vencimento: string, link: ?string}>,
     *   linkPendente: ?string
     * }
     */
    public function __invoke(): array
    {
        $id = $this->laboratorio->id();
        $assinatura = $this->assinaturas->doLaboratorio($id);
        $agora = $this->relogio->agora();
        $local = $agora->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        $consumo = $assinatura->consumo($this->pedidos->contar($id, (int) $local->format('Y'), (int) $local->format('n')));
        $situacao = $assinatura->situacao($agora);

        return [
            'plano' => $assinatura->plano()->value,
            'cobranca' => $assinatura->cobranca()->value,
            'situacao' => $situacao->value,
            'situacaoRotulo' => $situacao->rotulo(),
            'diasRestantes' => $assinatura->diasRestantes($agora),
            'validaAte' => $assinatura->validaAte()->format(DATE_ATOM),
            'pagoAte' => $assinatura->pagoAte()?->format(DATE_ATOM),
            'consumo' => [
                'mes' => $local->format('m/Y'), 'pedidos' => $consumo->pedidos, 'limite' => $consumo->limite(),
                'percentual' => $consumo->percentualUsado(), 'excedente' => $consumo->excedente(), 'valorExcedente' => $consumo->valorExcedenteCentavos(),
            ],
            'cobrancas' => array_map(static fn (CobrancaDoVidra $c): array => [
                'id' => $c->pagamentoId, 'tipo' => $c->tipo->value, 'tipoRotulo' => $c->tipo->rotulo(), 'competencia' => $c->competencia,
                'valor' => $c->valorCentavos, 'situacao' => $c->situacao->value, 'situacaoRotulo' => $c->situacao->rotulo(),
                'vencimento' => $c->vencimento, 'link' => $c->link,
            ], $this->cobrancas->recentes($id, 12)),
            'linkPendente' => $this->cobrancas->pendente($id)?->link,
        ];
    }
}
