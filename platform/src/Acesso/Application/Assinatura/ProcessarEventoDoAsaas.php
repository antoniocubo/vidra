<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application\Assinatura;

use Vidra\Acesso\Domain\Assinatura\AssinaturaRepository;
use Vidra\Acesso\Domain\Assinatura\CobrancaDoVidra;
use Vidra\Acesso\Domain\Assinatura\CobrancasDoVidra;
use Vidra\Acesso\Domain\Assinatura\SituacaoDaCobranca;
use Vidra\Acesso\Domain\Assinatura\TipoDeCobranca;
use Vidra\Acesso\Domain\Cobranca;
use Vidra\Acesso\Domain\Relogio;

/** Traduz o evento de pagamento do gateway. O Asaas reenvia eventos, então tudo aqui é idempotente. */
final readonly class ProcessarEventoDoAsaas
{
    public function __construct(
        private AssinaturaRepository $assinaturas,
        private CobrancasDoVidra $cobrancas,
        private Relogio $relogio,
    ) {}

    public function __invoke(EventoDoAsaas $evento): void
    {
        if (! in_array($evento->nome, ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED', 'PAYMENT_OVERDUE'], true)) {
            return;
        }
        $laboratorioId = $this->assinaturas->laboratorioDoCliente($evento->clienteId);
        if ($laboratorioId === null) {
            return;
        }

        // Cobranças recorrentes nascem no Asaas, não aqui: a primeira vez que aparecem, registramos.
        $tipo = $this->tipoDaAssinatura($laboratorioId);
        $cobranca = $this->cobrancas->registrar(new CobrancaDoVidra(
            $laboratorioId, $evento->pagamentoId, $tipo, $tipo === TipoDeCobranca::Mensalidade ? substr($evento->vencimento, 0, 7) : null, $evento->valorCentavos,
            SituacaoDaCobranca::Pendente, $evento->vencimento, $evento->link,
        ));

        if ($evento->nome === 'PAYMENT_OVERDUE') {
            $this->cobrancas->marcarVencida($evento->pagamentoId);

            return;
        }
        $agora = $this->relogio->agora();
        if (! $this->cobrancas->marcarPaga($evento->pagamentoId, $agora) || ! $cobranca->tipo->estendeAssinatura()) {
            return;
        }
        $assinatura = $this->assinaturas->doLaboratorio($laboratorioId);
        $assinatura->registrarPagamento($agora);
        $this->assinaturas->save($assinatura);
    }

    private function tipoDaAssinatura(string $laboratorioId): TipoDeCobranca
    {
        return $this->assinaturas->doLaboratorio($laboratorioId)->cobranca() === Cobranca::Anual ? TipoDeCobranca::Anuidade : TipoDeCobranca::Mensalidade;
    }
}
