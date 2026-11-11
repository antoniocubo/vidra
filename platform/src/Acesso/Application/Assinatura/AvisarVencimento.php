<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application\Assinatura;

use DateTimeZone;
use Vidra\Acesso\Domain\Assinatura\AssinaturaRepository;
use Vidra\Acesso\Domain\Assinatura\AvisosDeAssinatura;
use Vidra\Acesso\Domain\Assinatura\CobrancasDoVidra;
use Vidra\Acesso\Domain\Relogio;

/** Lembra os administradores do fim do teste ou do pagamento; no máximo um aviso por dia. */
final readonly class AvisarVencimento
{
    public function __construct(
        private AssinaturaRepository $assinaturas,
        private CobrancasDoVidra $cobrancas,
        private AvisosDeAssinatura $avisos,
        private Relogio $relogio,
    ) {}

    /** @return bool verdadeiro se enviou o aviso */
    public function __invoke(string $laboratorioId): bool
    {
        $agora = $this->relogio->agora();
        $assinatura = $this->assinaturas->doLaboratorio($laboratorioId);
        $hoje = $agora->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('Y-m-d');
        $emails = $this->assinaturas->emailsDosAdministradores($laboratorioId);
        if (! $assinatura->deveAvisar($agora) || $this->assinaturas->avisadaEm($laboratorioId) === $hoje || $emails === []) {
            return false;
        }

        $this->avisos->enviar(
            $emails, $this->assinaturas->cliente($laboratorioId)->nome, $assinatura->situacao($agora),
            $assinatura->diasRestantes($agora), $this->cobrancas->pendente($laboratorioId)?->link,
        );
        $this->assinaturas->marcarAvisada($laboratorioId, $agora);

        return true;
    }
}
