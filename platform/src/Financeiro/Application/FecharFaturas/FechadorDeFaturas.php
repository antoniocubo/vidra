<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Application\FecharFaturas;

use DateTimeImmutable;
use Vidra\Financeiro\Domain\Calendario;
use Vidra\Financeiro\Domain\Competencia;
use Vidra\Financeiro\Domain\EmissorDeFatura;
use Vidra\Financeiro\Domain\Fatura;
use Vidra\Financeiro\Domain\FaturaPendente;
use Vidra\Financeiro\Domain\FaturaRepository;
use Vidra\Financeiro\Domain\PedidosFaturaveis;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Shared\Domain\Transacao;

/** O miolo do fechamento: transforma os pedidos pendentes de uma competência em faturas e manda enviar. */
final readonly class FechadorDeFaturas
{
    public function __construct(
        private PedidosFaturaveis $pedidos,
        private FaturaRepository $faturas,
        private EmissorDeFatura $emissor,
        private LaboratorioAtual $laboratorio,
        private Transacao $transacao,
    ) {}

    /**
     * @param  callable(FaturaPendente): bool  $quais
     * @return int quantas faturas foram fechadas
     */
    public function fechar(Competencia $competencia, DateTimeImmutable $agora, callable $quais): int
    {
        $fechadas = 0;
        foreach ($this->pedidos->daCompetencia($competencia) as $pendente) {
            if (! $quais($pendente) || $this->faturas->doPagadorNaCompetencia($pendente->dados->pagador, $competencia) !== null) {
                continue;
            }
            $fatura = $this->transacao->executar(fn (): Fatura => $this->gravar($pendente, $competencia, $agora));
            // Sem e-mail de faturamento a fatura fica aberta, à vista no painel, até alguém corrigir o cadastro.
            if ($fatura->pagador->email !== null) {
                $this->emissor->enfileirar($fatura);
            }
            $fechadas++;
        }

        return $fechadas;
    }

    private function gravar(FaturaPendente $pendente, Competencia $competencia, DateTimeImmutable $agora): Fatura
    {
        $dados = $pendente->dados;
        $fatura = Fatura::fechar(
            $this->faturas->nextIdentity(), $this->laboratorio->id(), $this->faturas->proximoNumero(), $dados->pagador, $competencia,
            Calendario::vencimento($dados->diaDeFechamento, $dados->diaDeVencimento, $agora), $pendente->linhas, $agora,
        );
        $this->faturas->save($fatura);

        return $fatura;
    }
}
