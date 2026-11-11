<?php

declare(strict_types=1);

namespace Vidra\Auditoria\Application;

use DateTimeZone;
use Vidra\Auditoria\Domain\AcaoAuditada;
use Vidra\Auditoria\Domain\QuemFez;
use Vidra\Auditoria\Domain\RegistroDeAuditoria;
use Vidra\Auditoria\Domain\TrilhaDeAuditoria;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class RegistrarNaTrilha
{
    public function __construct(private TrilhaDeAuditoria $trilha, private QuemFez $quem, private LaboratorioAtual $laboratorio) {}

    public function __invoke(string $acao, string $alvoTipo, string $alvoId, string $detalhe, ?string $pedidoId = null): void
    {
        $this->gravar($acao, $alvoTipo, $alvoId, $detalhe, $pedidoId, $this->laboratorio->id(), $this->quem->usuarioId(), $this->quem->nome(), $this->quem->ip());
    }

    // Só os fluxos públicos fornecem o laboratório descoberto por um token validado.
    public function aberturaPublica(string $laboratorioId, string $laudoId, string $pedidoId, string $ip): void
    {
        $this->gravar('laudo_aberto_pelo_tutor', 'laudo', $laudoId, 'Tutor abriu pelo link', $pedidoId, $laboratorioId, null, 'Tutor', $ip);
    }

    public function aceiteDeConvite(string $laboratorioId, int $usuarioId, string $nome): void
    {
        $this->gravar('convite_aceito', 'equipe', (string) $usuarioId, 'Aceitou o convite para a equipe', null, $laboratorioId, $usuarioId, $nome, $this->quem->ip());
    }

    private function gravar(string $acao, string $alvoTipo, string $alvoId, string $detalhe, ?string $pedidoId, string $laboratorioId, ?int $usuarioId, string $nome, ?string $ip): void
    {
        $this->trilha->registrar(new RegistroDeAuditoria($this->trilha->nextIdentity(), $laboratorioId, AcaoAuditada::from($acao), $alvoTipo, $alvoId, $pedidoId, $usuarioId, $nome, $detalhe, $ip, $this->quem->agora()->setTimezone(new DateTimeZone('UTC'))));
    }
}
