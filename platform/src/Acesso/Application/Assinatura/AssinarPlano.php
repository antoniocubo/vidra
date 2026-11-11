<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application\Assinatura;

use DateTimeImmutable;
use DateTimeZone;
use Vidra\Acesso\Domain\Assinatura\AssinaturaRepository;
use Vidra\Acesso\Domain\Assinatura\CobrancaDoVidra;
use Vidra\Acesso\Domain\Assinatura\CobrancasDoVidra;
use Vidra\Acesso\Domain\Assinatura\GatewayDeCobranca;
use Vidra\Acesso\Domain\Assinatura\SituacaoDaCobranca;
use Vidra\Acesso\Domain\Assinatura\TipoDeCobranca;
use Vidra\Acesso\Domain\Cobranca;
use Vidra\Acesso\Domain\Plano;
use Vidra\Acesso\Domain\Relogio;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class AssinarPlano
{
    public function __construct(
        private LaboratorioAtual $laboratorio,
        private AssinaturaRepository $assinaturas,
        private CobrancasDoVidra $cobrancas,
        private GatewayDeCobranca $gateway,
        private Relogio $relogio,
    ) {}

    public function __invoke(AssinarPlanoInput $input): void
    {
        $id = $this->laboratorio->id();
        $plano = Plano::from($input->plano);
        $cobranca = Cobranca::from($input->cobranca);
        $assinatura = $this->assinaturas->doLaboratorio($id);
        $cliente = $this->assinaturas->cliente($id);
        $agora = $this->relogio->agora();

        $clienteId = $this->gateway->garantirCliente($cliente);
        if ($cliente->clienteId !== $clienteId) {
            $this->assinaturas->vincularCliente($id, $clienteId);
        }
        if ($cliente->assinaturaId !== null) {
            $this->gateway->cancelarAssinatura($cliente->assinaturaId);
            $this->assinaturas->vincularAssinatura($id, null);
        }

        // Quem ainda tem teste ou período pago só começa a pagar quando ele acaba; senão a troca cobraria em dobro.
        $vencimento = $assinatura->validaAte() > $agora ? $assinatura->validaAte() : $agora;
        $vencimento = new DateTimeImmutable($vencimento->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('Y-m-d'), new DateTimeZone('America/Sao_Paulo'));
        $criada = $this->gateway->criarAssinatura($clienteId, $plano, $cobranca, $vencimento);

        $assinatura->trocarPlano($plano, $cobranca);
        $this->assinaturas->save($assinatura);
        $this->assinaturas->vincularAssinatura($id, $criada['id']);

        if ($criada['primeiroPagamento'] !== null) {
            $pagamento = $criada['primeiroPagamento'];
            $mensal = $cobranca === Cobranca::Mensal;
            $this->cobrancas->registrar(new CobrancaDoVidra(
                $id, $pagamento->id, $mensal ? TipoDeCobranca::Mensalidade : TipoDeCobranca::Anuidade, $mensal ? substr($pagamento->vencimento, 0, 7) : null,
                $pagamento->valorCentavos, SituacaoDaCobranca::Pendente, $pagamento->vencimento, $pagamento->link,
            ));
        }
    }
}
