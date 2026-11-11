<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application\Assinatura;

use DateInterval;
use Vidra\Acesso\Domain\Assinatura\AssinaturaRepository;
use Vidra\Acesso\Domain\Assinatura\CobrancaDoVidra;
use Vidra\Acesso\Domain\Assinatura\CobrancasDoVidra;
use Vidra\Acesso\Domain\Assinatura\GatewayDeCobranca;
use Vidra\Acesso\Domain\Assinatura\PedidosDoMes;
use Vidra\Acesso\Domain\Assinatura\SituacaoDaAssinatura;
use Vidra\Acesso\Domain\Assinatura\SituacaoDaCobranca;
use Vidra\Acesso\Domain\Assinatura\TipoDeCobranca;
use Vidra\Acesso\Domain\Relogio;

/** Cobra, uma vez por competência, os pedidos que passaram do limite do plano. Roda por laboratório. */
final readonly class CobrarExcedentes
{
    public const int DIAS_PARA_PAGAR = 7;

    public function __construct(
        private AssinaturaRepository $assinaturas,
        private CobrancasDoVidra $cobrancas,
        private PedidosDoMes $pedidos,
        private GatewayDeCobranca $gateway,
        private Relogio $relogio,
    ) {}

    /** @return bool verdadeiro se gerou uma cobrança */
    public function __invoke(string $laboratorioId, int $ano, int $mes): bool
    {
        $competencia = sprintf('%04d-%02d', $ano, $mes);
        $agora = $this->relogio->agora();
        $assinatura = $this->assinaturas->doLaboratorio($laboratorioId);
        // Teste não paga, e quem está só consultando não tem plano em vigor para cobrar.
        if (! in_array($assinatura->situacao($agora), [SituacaoDaAssinatura::Ativa, SituacaoDaAssinatura::Atrasada], true)) {
            return false;
        }
        $consumo = $assinatura->consumo($this->pedidos->contar($laboratorioId, $ano, $mes));
        if ($consumo->excedente() === 0 || $this->cobrancas->existeExcedente($laboratorioId, $competencia)) {
            return false;
        }

        $cliente = $this->assinaturas->cliente($laboratorioId);
        $clienteId = $this->gateway->garantirCliente($cliente);
        if ($cliente->clienteId !== $clienteId) {
            $this->assinaturas->vincularCliente($laboratorioId, $clienteId);
        }
        $pagamento = $this->gateway->cobrarAvulso(
            $clienteId, $consumo->valorExcedenteCentavos(), $agora->add(new DateInterval('P'.self::DIAS_PARA_PAGAR.'D')),
            sprintf('Excedente de %d pedidos em %02d/%04d', $consumo->excedente(), $mes, $ano),
        );
        $this->cobrancas->registrar(new CobrancaDoVidra(
            $laboratorioId, $pagamento->id, TipoDeCobranca::Excedente, $competencia, $pagamento->valorCentavos, SituacaoDaCobranca::Pendente, $pagamento->vencimento, $pagamento->link,
        ));

        return true;
    }
}
