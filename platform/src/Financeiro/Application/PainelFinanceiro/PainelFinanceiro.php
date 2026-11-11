<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Application\PainelFinanceiro;

use DateTimeImmutable;
use DateTimeZone;
use Vidra\Financeiro\Domain\Competencia;
use Vidra\Financeiro\Domain\Fatura;
use Vidra\Financeiro\Domain\FaturaPendente;
use Vidra\Financeiro\Domain\FaturaRepository;
use Vidra\Financeiro\Domain\FaturasEmAtraso;
use Vidra\Financeiro\Domain\LinhaDaFatura;
use Vidra\Financeiro\Domain\NotaDeCredito;
use Vidra\Financeiro\Domain\PedidosFaturaveis;
use Vidra\Financeiro\Domain\SituacaoDaFatura;
use Vidra\Financeiro\Domain\TipoDePagadorDaFatura;

/** Tudo o que a tela do financeiro mostra para uma competência, já em tipos simples. Dinheiro em centavos, datas em ISO. */
final readonly class PainelFinanceiro
{
    public function __construct(private FaturaRepository $faturas, private PedidosFaturaveis $pedidos, private FaturasEmAtraso $emAtraso) {}

    /**
     * @return array{
     *   competencia: string,
     *   competencias: list<array{value: string, label: string}>,
     *   resumo: array{clinicas: int, clinicasPedidos: int, solicitantes: int, solicitantesPedidos: int, recebido: int, emAtraso: int, emAtrasoQuem: string},
     *   pagadores: list<array<string, mixed>>,
     *   fatura: array<string, mixed>|null
     * }
     */
    public function __invoke(?string $competencia = null, ?string $faturaId = null): array
    {
        $agora = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $escolhida = $competencia === null ? Competencia::anteriorA($agora) : Competencia::de($competencia);
        $faturas = $this->faturas->daCompetencia($escolhida);
        $pendentes = $this->pedidos->daCompetencia($escolhida);

        $pagadores = [...array_map(fn (Fatura $f): array => $this->linhaDaFatura($f, $agora), $faturas), ...array_map($this->linhaPendente(...), $pendentes)];
        usort($pagadores, fn (array $a, array $b): int => strcmp(mb_strtolower($a['pagador']), mb_strtolower($b['pagador'])));

        $selecionada = $faturaId === null ? null : $this->faturas->porId($faturaId);

        return [
            'competencia' => $escolhida->valor(),
            'competencias' => $this->ultimasCompetencias($agora),
            'resumo' => $this->resumo($escolhida, $faturas, $pendentes, $agora),
            'pagadores' => $pagadores,
            'fatura' => $selecionada === null ? null : $this->detalhe($selecionada, $agora),
        ];
    }

    private function dia(DateTimeImmutable $quando): string
    {
        return $quando->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('Y-m-d');
    }

    /** @return list<array{value: string, label: string}> */
    private function ultimasCompetencias(DateTimeImmutable $agora): array
    {
        $primeiro = $agora->setTimezone(new DateTimeZone('America/Sao_Paulo'))->modify('first day of this month');
        $lista = [];
        for ($i = 0; $i < 12; $i++) {
            $mes = $primeiro->modify("-{$i} months");
            $c = new Competencia((int) $mes->format('Y'), (int) $mes->format('n'));
            $lista[] = ['value' => $c->valor(), 'label' => $c->rotulo()];
        }

        return $lista;
    }

    /**
     * @param  list<Fatura>  $faturas
     * @param  list<FaturaPendente>  $pendentes
     * @return array{clinicas: int, clinicasPedidos: int, solicitantes: int, solicitantesPedidos: int, recebido: int, emAtraso: int, emAtrasoQuem: string}
     */
    private function resumo(Competencia $competencia, array $faturas, array $pendentes, DateTimeImmutable $agora): array
    {
        $deClinicas = array_filter($pendentes, fn (FaturaPendente $p): bool => $p->dados->pagador->tipo === TipoDePagadorDaFatura::Clinica);
        $deSolicitantes = array_filter($pendentes, fn (FaturaPendente $p): bool => $p->dados->pagador->tipo === TipoDePagadorDaFatura::Solicitante);
        $pagas = array_filter($faturas, fn (Fatura $f): bool => $f->situacao($agora) === SituacaoDaFatura::Paga);
        $atrasadas = array_filter($this->emAtraso->naoPagasAnterioresA($competencia), fn (Fatura $f): bool => $f->situacao($agora) === SituacaoDaFatura::Vencida);

        return [
            'clinicas' => array_sum(array_map(fn (FaturaPendente $p): int => $p->total(), $deClinicas)),
            'clinicasPedidos' => $this->contarPedidos($deClinicas),
            'solicitantes' => array_sum(array_map(fn (FaturaPendente $p): int => $p->total(), $deSolicitantes)),
            'solicitantesPedidos' => $this->contarPedidos($deSolicitantes),
            'recebido' => array_sum(array_map(fn (Fatura $f): int => $f->total(), $pagas)),
            'emAtraso' => array_sum(array_map(fn (Fatura $f): int => $f->total(), $atrasadas)),
            'emAtrasoQuem' => implode(', ', array_unique(array_map(fn (Fatura $f): string => $f->pagador->nome, $atrasadas))),
        ];
    }

    /** @param array<FaturaPendente> $pendentes */
    private function contarPedidos(array $pendentes): int
    {
        return array_sum(array_map(fn (FaturaPendente $p): int => count($p->linhas), $pendentes));
    }

    /** @return array<string, mixed> */
    private function linhaDaFatura(Fatura $fatura, DateTimeImmutable $agora): array
    {
        $situacao = $fatura->situacao($agora);

        return [
            'id' => $fatura->id, 'numero' => $fatura->numeroFormatado(), 'pagador' => $fatura->pagador->nome, 'tipo' => $fatura->pagador->tipo->value,
            'pedidos' => count($fatura->linhas), 'valor' => $fatura->total(), 'situacao' => $situacao->value, 'situacaoRotulo' => $situacao->rotulo(),
            'vencimento' => $this->dia($fatura->vencimento),
        ];
    }

    /** @return array<string, mixed> */
    private function linhaPendente(FaturaPendente $pendente): array
    {
        $pagador = $pendente->dados->pagador;

        return [
            'id' => null, 'numero' => null, 'pagador' => $pagador->nome, 'tipo' => $pagador->tipo->value, 'pedidos' => count($pendente->linhas), 'valor' => $pendente->total(),
            'situacao' => 'a_fechar', 'situacaoRotulo' => 'A fechar', 'vencimento' => null,
        ];
    }

    /** @return array<string, mixed> */
    private function detalhe(Fatura $fatura, DateTimeImmutable $agora): array
    {
        $situacao = $fatura->situacao($agora);

        return [
            'id' => $fatura->id, 'numero' => $fatura->numeroFormatado(), 'competencia' => $fatura->competencia->rotulo(), 'pagador' => $fatura->pagador->nome,
            'email' => $fatura->pagador->email, 'vencimento' => $this->dia($fatura->vencimento), 'situacao' => $situacao->value, 'situacaoRotulo' => $situacao->rotulo(),
            'enviadaEm' => $fatura->enviadaEm()?->format('c'), 'pagaEm' => $fatura->pagaEm()?->format('c'),
            'subtotal' => $fatura->subtotal(), 'totalCreditos' => $fatura->totalCreditos(), 'total' => $fatura->total(),
            'emCobranca' => $situacao === SituacaoDaFatura::Enviada || $situacao === SituacaoDaFatura::Vencida,
            'linhas' => array_map(fn (LinhaDaFatura $l): array => ['protocolo' => $l->protocolo, 'paciente' => $l->paciente, 'liberadoEm' => $l->liberadoEm->format('c'), 'exames' => $l->exames, 'valor' => $l->valorCentavos], $fatura->linhas),
            'creditos' => array_map(fn (NotaDeCredito $c): array => ['motivo' => $c->motivo, 'valor' => $c->valorCentavos, 'em' => $c->em->format('c')], $fatura->creditos()),
        ];
    }
}
